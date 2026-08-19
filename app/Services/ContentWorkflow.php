<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\GalleryAlbum;
use App\Models\GalleryItem;
use App\Models\LayoutItem;
use App\Models\MediaAttachment;
use App\Models\MediaFile;
use App\Models\Officer;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentWorkflow
{
    public function __construct(private readonly MediaManager $mediaManager) {}

    private const CONTENT_MODELS = [Page::class, ContentItem::class, Officer::class, GalleryAlbum::class];
    private const PROTECTED = ['id', 'status', 'created_by', 'updated_by', 'published_by', 'submitted_at', 'published_at', 'created_at', 'updated_at', 'deleted_at'];

    public function saveDraft(Model $content, array $attributes, User $actor, ?string $note = null): Model|ContentRevision
    {
        $this->ensureContent($content);
        $this->ensureEditor($actor);
        $attributes = Arr::except($attributes, self::PROTECTED);

        return DB::transaction(function () use ($content, $attributes, $actor, $note) {
            if ($content->exists) {
                // Serializing on the live record prevents concurrent requests from
                // creating two working proposals for the same editor and content.
                $content = $content->newQuery()->lockForUpdate()->findOrFail($content->getKey());
            }

            // Keep the currently published version live while an editor prepares changes.
            if ($content->exists && $content->getAttribute('status') === 'published' && ! $actor->isPublisher()) {
                // An editor has one working proposal per live record. A submitted
                // proposal must be reviewed before it can be replaced; draft or
                // rejected proposals are superseded and their staged files cleaned.
                $working = ContentRevision::query()
                    ->where('revisionable_type', $content->getMorphClass())
                    ->where('revisionable_id', $content->getKey())
                    ->where('user_id', $actor->id)
                    ->where('is_working', true)
                    ->lockForUpdate()
                    ->get();
                if ($working->contains(fn (ContentRevision $revision): bool => $revision->action === 'submitted')) {
                    throw ValidationException::withMessages([
                        'status' => __('Your submitted revision must be reviewed before you can create another one.'),
                    ]);
                }
                $working->whereIn('action', ['draft', 'rejected'])->each(function (ContentRevision $draft): void {
                    $this->discardPendingMedia($draft);
                    $draft->update(['action' => 'superseded', 'is_working' => false]);
                });

                $snapshot = array_merge(Arr::except($content->getAttributes(), self::PROTECTED), $attributes);
                $snapshot['_base_updated_at'] = $content->getRawOriginal('updated_at');
                $revision = ContentRevision::create([
                    'revisionable_type' => $content->getMorphClass(),
                    'revisionable_id' => $content->getKey(),
                    'snapshot' => $snapshot,
                    'action' => 'draft',
                    'is_working' => true,
                    'note' => $note,
                    'user_id' => $actor->id,
                ]);
                $this->audit('revision.created', $revision, null, ['content_type' => $content::class, 'content_id' => $content->getKey()], $actor);
                return $revision;
            }

            // A publisher editing live content updates the live version in place; a plain
            // "save" must never temporarily unpublish an already-published record.
            if ($content->exists && $content->getAttribute('status') === 'published' && $actor->isPublisher()) {
                $old = $content->getOriginal();
                $content->fill($attributes);
                $content->forceFill(['status' => 'published', 'updated_by' => $actor->id])->save();
                if ($content instanceof Officer) {
                    $this->deleteMediaIfUnused($old['photo_media_id'] ?? null);
                }
                $this->revision($content, 'published_update', $actor, $note);
                $this->audit('content.updated', $content, $old, $content->getAttributes(), $actor);
                return $content;
            }

            $old = $content->exists ? $content->getOriginal() : null;
            $content->fill($attributes);
            $content->setAttribute('status', 'draft');
            $content->setAttribute($content->exists ? 'updated_by' : 'created_by', $actor->id);
            if (! $content->exists) $content->setAttribute('updated_by', $actor->id);
            $content->save();
            if ($content instanceof Officer) {
                $this->deleteMediaIfUnused($old['photo_media_id'] ?? null);
            }
            $this->revision($content, 'draft', $actor, $note);
            $this->audit($old ? 'content.updated' : 'content.created', $content, $old, $content->getAttributes(), $actor);
            return $content;
        });
    }

    public function submit(Model $content, User $actor, ?string $note = null): Model
    {
        $this->ensureContent($content);
        $this->ensureEditor($actor);
        if (! in_array($content->getAttribute('status'), ['draft', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => __('Only draft or rejected content can be submitted.')]);
        }

        return DB::transaction(function () use ($content, $actor, $note) {
            $old = $content->getOriginal();
            $content->forceFill(['status' => 'pending_review', 'submitted_at' => now(), 'updated_by' => $actor->id])->save();
            $this->revision($content, 'submitted', $actor, $note);
            $this->audit('content.submitted', $content, $old, $content->getAttributes(), $actor);
            return $content;
        });
    }

    public function submitRevision(ContentRevision $revision, User $actor, ?string $note = null): ContentRevision
    {
        $this->ensureEditor($actor);

        return DB::transaction(function () use ($revision, $actor, $note): ContentRevision {
            $revision = ContentRevision::query()->lockForUpdate()->findOrFail($revision->getKey());
            abort_unless($revision->is_working && ($revision->user_id === $actor->id || $actor->isPublisher()), 403);
            if ($revision->action !== 'draft') {
                throw ValidationException::withMessages(['status' => __('Only draft revisions can be submitted. Create a new draft to revise rejected changes.')]);
            }
            $revision->update(['action' => 'submitted', 'note' => $note ?? $revision->note]);
            $this->audit('revision.submitted', $revision, null, $revision->getAttributes(), $actor);
            return $revision;
        });
    }

    public function publish(Model $content, User $publisher, ?string $note = null): Model
    {
        $this->ensureContent($content);
        $this->ensurePublisher($publisher);
        if (! in_array($content->getAttribute('status'), ['pending_review', 'draft', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => __('This content cannot be published from its current state.')]);
        }

        return DB::transaction(function () use ($content, $publisher, $note) {
            $old = $content->getOriginal();
            $content->forceFill([
                'status' => 'published', 'published_at' => now(), 'published_by' => $publisher->id, 'updated_by' => $publisher->id,
            ])->save();
            $this->revision($content, 'published', $publisher, $note);
            $this->audit('content.published', $content, $old, $content->getAttributes(), $publisher);
            return $content;
        });
    }

    public function publishRevision(ContentRevision $revision, User $publisher, ?string $note = null): Model
    {
        $this->ensurePublisher($publisher);

        return DB::transaction(function () use ($revision, $publisher, $note) {
            // Draft creation locks content before its revisions, so publication uses
            // the same lock order to avoid a content/revision deadlock.
            $candidate = ContentRevision::query()->findOrFail($revision->getKey());
            $class = $candidate->revisionable_type;
            abort_unless(in_array($class, self::CONTENT_MODELS, true), 422);
            $content = $class::query()->lockForUpdate()->findOrFail($candidate->revisionable_id);
            $revision = ContentRevision::query()->lockForUpdate()->findOrFail($candidate->getKey());
            if ($revision->revisionable_type !== $class || (string) $revision->revisionable_id !== (string) $content->getKey()
                || ! $revision->is_working || $revision->action !== 'submitted') {
                throw ValidationException::withMessages(['status' => __('Only submitted revisions can be published.')]);
            }
            $old = $content->getOriginal();
            $snapshot = $revision->snapshot;
            $baseUpdatedAt = Arr::pull($snapshot, '_base_updated_at');
            if (! is_string($baseUpdatedAt) || (string) $content->getRawOriginal('updated_at') !== $baseUpdatedAt) {
                throw ValidationException::withMessages([
                    'status' => __('The published content changed after this draft was created. Ask the editor to create a fresh revision.'),
                ]);
            }
            $pendingAttachments = Arr::pull($snapshot, '_pending_attachments', []);
            $removeAttachmentIds = Arr::pull($snapshot, '_remove_attachment_ids', []);
            $content->fill(Arr::except($snapshot, self::PROTECTED));
            $content->forceFill(['status' => 'published', 'published_at' => now(), 'published_by' => $publisher->id, 'updated_by' => $publisher->id])->save();
            if ($content instanceof ContentItem) {
                $removedMediaIds = MediaAttachment::where('attachable_type', $content->getMorphClass())
                    ->where('attachable_id', $content->id)
                    ->whereIn('id', $removeAttachmentIds)
                    ->pluck('media_file_id');
                MediaAttachment::where('attachable_type', $content->getMorphClass())
                    ->where('attachable_id', $content->id)
                    ->whereIn('id', $removeAttachmentIds)
                    ->delete();
                foreach ($pendingAttachments as $attachment) $content->attachments()->create(Arr::only($attachment, ['media_file_id', 'role', 'label_bn', 'label_en', 'sort_order']));
                foreach ($removedMediaIds as $mediaId) $this->deleteMediaIfUnused($mediaId, $revision);
            }
            if ($content instanceof Officer) {
                $this->deleteMediaIfUnused($old['photo_media_id'] ?? null, $revision);
            }
            $revision->update(['action' => 'published', 'is_working' => false, 'note' => $note ?? $revision->note]);
            $this->revision($content, 'published', $publisher, $note);
            $this->audit('revision.published', $content, $old, $content->getAttributes(), $publisher);
            return $content;
        });
    }

    public function reject(Model|ContentRevision $subject, User $publisher, string $note): Model|ContentRevision
    {
        $this->ensurePublisher($publisher);
        if (! filled($note)) throw ValidationException::withMessages(['note' => __('A rejection reason is required.')]);

        if ($subject instanceof ContentRevision) {
            return DB::transaction(function () use ($subject, $publisher, $note): ContentRevision {
                $subject = ContentRevision::query()->lockForUpdate()->findOrFail($subject->getKey());
                abort_unless($subject->is_working && $subject->action === 'submitted', 422);
                $old = $subject->getAttributes();
                $this->discardPendingMedia($subject, true);
                $subject->update(['action' => 'rejected', 'note' => $note]);
                $this->audit('revision.rejected', $subject, $old, $subject->getAttributes(), $publisher);
                return $subject;
            });
        }

        $this->ensureContent($subject);
        abort_unless($subject->getAttribute('status') === 'pending_review', 422);
        $subject->forceFill(['status' => 'rejected', 'updated_by' => $publisher->id])->save();
        $this->revision($subject, 'rejected', $publisher, $note);
        $this->audit('content.rejected', $subject, null, $subject->getAttributes(), $publisher);
        return $subject;
    }

    public function restoreOfficer(Officer $officer, User $publisher): Officer
    {
        $this->ensurePublisher($publisher);
        abort_unless($officer->trashed(), 422);
        $officer->restore();
        $this->audit('officer.restored', $officer, null, $officer->getAttributes(), $publisher);

        return $officer;
    }

    public function forceDeleteOfficer(Officer $officer, User $publisher): void
    {
        $this->ensurePublisher($publisher);
        abort_unless($officer->trashed(), 422);

        DB::transaction(function () use ($officer, $publisher): void {
            $old = $officer->getAttributes();
            $photoMediaId = $officer->photo_media_id;
            $revisions = ContentRevision::query()
                ->where('revisionable_type', $officer->getMorphClass())
                ->where('revisionable_id', $officer->id)
                ->get();
            foreach ($revisions->where('is_working', true) as $revision) $this->discardPendingMedia($revision);
            ContentRevision::query()
                ->where('revisionable_type', $officer->getMorphClass())
                ->where('revisionable_id', $officer->id)
                ->delete();
            $officer->forceDelete();
            $this->deleteMediaIfUnused($photoMediaId);
            $this->audit('officer.permanently_deleted', $officer, $old, null, $publisher);
        });
    }

    public function discardRevision(ContentRevision $revision, User $actor): void
    {
        $this->ensureEditor($actor);

        DB::transaction(function () use ($revision, $actor): void {
            $revision = ContentRevision::query()->lockForUpdate()->findOrFail($revision->getKey());
            abort_unless($revision->is_working && ($actor->isPublisher() || $revision->user_id === $actor->id), 403);
            if (! in_array($revision->action, ['draft', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => __('Only draft or rejected revisions can be discarded.')]);
            }
            $old = $revision->getAttributes();
            $this->discardPendingMedia($revision);
            $this->audit('revision.discarded', $revision, $old, null, $actor);
            $revision->delete();
        });
    }

    private function discardPendingMedia(ContentRevision $revision, bool $saveSnapshot = false): void
    {
        $snapshot = $revision->snapshot ?? [];
        $pending = Arr::pull($snapshot, '_pending_attachments', []);
        Arr::forget($snapshot, '_remove_attachment_ids');

        if ($saveSnapshot) {
            $revision->update(['snapshot' => $snapshot]);
        }

        $mediaIds = collect($pending)->pluck('media_file_id')->filter();
        if ($revision->revisionable_type === (new Officer)->getMorphClass()) {
            $livePhotoId = Officer::query()->whereKey($revision->revisionable_id)->value('photo_media_id');
            $proposedPhotoId = $revision->snapshot['photo_media_id'] ?? null;
            if ($proposedPhotoId && (int) $proposedPhotoId !== (int) $livePhotoId) {
                $mediaIds->push($proposedPhotoId);
            }
        }

        foreach ($mediaIds->unique() as $mediaId) {
            $this->deleteMediaIfUnused($mediaId, $revision);
        }
    }

    public function cleanUnusedMedia(mixed $mediaId): void
    {
        $this->deleteMediaIfUnused($mediaId);
    }

    public function mediaIsUsed(MediaFile $media): bool
    {
        return $this->mediaIsReferenced($media);
    }

    private function deleteMediaIfUnused(mixed $mediaId, ?ContentRevision $excluding = null): void
    {
        if (! $mediaId) return;

        if (DB::transactionLevel() > 0) {
            $excludedId = $excluding?->id;
            DB::afterCommit(function () use ($mediaId, $excludedId): void {
                $excluded = $excludedId ? ContentRevision::query()->find($excludedId) : null;
                $this->deleteMediaIfUnusedNow($mediaId, $excluded);
            });
            return;
        }

        $this->deleteMediaIfUnusedNow($mediaId, $excluding);
    }

    private function deleteMediaIfUnusedNow(mixed $mediaId, ?ContentRevision $excluding = null): void
    {
        if (! ($media = MediaFile::query()->find($mediaId)) || $this->mediaIsReferenced($media, $excluding)) return;
        $this->mediaManager->delete($media);
    }

    private function mediaIsReferenced(MediaFile $media, ?ContentRevision $excluding = null): bool
    {
        if (MediaAttachment::query()->where('media_file_id', $media->id)->exists()
            || GalleryItem::query()->where('media_file_id', $media->id)->exists()
            || LayoutItem::query()->where('media_file_id', $media->id)->exists()
            || Officer::withTrashed()->where('photo_media_id', $media->id)->exists()) {
            return true;
        }

        return ContentRevision::query()
            ->when($excluding, fn ($query) => $query->where('id', '!=', $excluding->id))
            ->where('is_working', true)
            ->whereIn('action', ['draft', 'submitted', 'rejected'])
            ->get(['snapshot'])
            ->contains(function (ContentRevision $revision) use ($media): bool {
                if ((int) ($revision->snapshot['photo_media_id'] ?? 0) === $media->id) return true;

                return collect($revision->snapshot['_pending_attachments'] ?? [])
                    ->contains(fn (array $attachment): bool => (int) ($attachment['media_file_id'] ?? 0) === $media->id);
            });
    }

    private function revision(Model $content, string $action, User $actor, ?string $note): ContentRevision
    {
        return ContentRevision::create([
            'revisionable_type' => $content->getMorphClass(),
            'revisionable_id' => $content->getKey(),
            'snapshot' => Arr::except($content->getAttributes(), ['password', 'remember_token']),
            'action' => $action,
            'note' => $note,
            'user_id' => $actor->id,
        ]);
    }

    private function audit(string $event, Model $subject, ?array $old, ?array $new, User $actor): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'event' => $event,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }

    private function ensureContent(Model $content): void
    {
        abort_unless(in_array($content::class, self::CONTENT_MODELS, true), 422);
    }

    private function ensureEditor(User $actor): void
    {
        abort_unless($actor->is_active && in_array($actor->role, ['editor', 'publisher', 'super_admin'], true), 403);
    }

    private function ensurePublisher(User $actor): void
    {
        abort_unless($actor->is_active && $actor->isPublisher(), 403);
    }
}
