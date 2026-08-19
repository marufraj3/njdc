<?php

namespace Tests\Feature;

use App\Models\ContentRevision;
use App\Models\MediaFile;
use App\Models\Officer;
use App\Models\Page;
use App\Models\User;
use App\Services\ContentWorkflow;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowMediaCleanupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_rejecting_a_revision_removes_media_staged_only_for_it(): void
    {
        Storage::fake('uploads');
        $editor = User::factory()->create(['role' => 'editor']);
        $publisher = User::factory()->create(['role' => 'publisher']);
        $page = Page::create(['path' => '/pages/live', 'title_bn' => 'Live', 'status' => 'published']);
        $media = $this->media($editor, 'staged.pdf');
        $revision = ContentRevision::create([
            'revisionable_type' => $page->getMorphClass(),
            'revisionable_id' => $page->id,
            'snapshot' => [
                'title_bn' => 'Changed',
                '_pending_attachments' => [['media_file_id' => $media->id, 'role' => 'attachment']],
            ],
            'action' => 'submitted',
            'is_working' => true,
            'user_id' => $editor->id,
        ]);

        app(ContentWorkflow::class)->reject($revision, $publisher, 'Please revise it.');

        $this->assertSoftDeleted('media_files', ['id' => $media->id]);
        Storage::disk('uploads')->assertMissing('testing/staged.pdf');
        $this->assertArrayNotHasKey('_pending_attachments', $revision->fresh()->snapshot);
    }

    public function test_rejecting_an_officer_photo_revision_removes_the_proposed_upload_and_restarts_from_live_photo(): void
    {
        Storage::fake('uploads');
        $editor = User::factory()->create(['role' => 'editor']);
        $publisher = User::factory()->create(['role' => 'publisher']);
        $livePhoto = $this->media($publisher, 'live.jpg');
        $proposedPhoto = $this->media($editor, 'proposed.jpg');
        $officer = Officer::create([
            'path' => '/pages/officers/rejected-photo',
            'name_bn' => 'Officer',
            'photo_media_id' => $livePhoto->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $workflow = app(ContentWorkflow::class);
        $rejected = $workflow->saveDraft($officer, ['photo_media_id' => $proposedPhoto->id], $editor);
        $workflow->submitRevision($rejected, $editor);

        $workflow->reject($rejected->fresh(), $publisher, 'Use another photo.');
        $replacement = $workflow->saveDraft($officer->fresh(), ['designation_bn' => 'Updated'], $editor);

        $this->assertSoftDeleted('media_files', ['id' => $proposedPhoto->id]);
        Storage::disk('uploads')->assertMissing('testing/proposed.jpg');
        $this->assertDatabaseHas('media_files', ['id' => $livePhoto->id, 'deleted_at' => null]);
        $this->assertSame($livePhoto->id, $replacement->snapshot['photo_media_id']);
        $this->assertSame('superseded', $rejected->fresh()->action);
    }

    public function test_publishing_an_officer_photo_revision_removes_the_replaced_upload(): void
    {
        Storage::fake('uploads');
        $editor = User::factory()->create(['role' => 'editor']);
        $publisher = User::factory()->create(['role' => 'publisher']);
        $old = $this->media($publisher, 'old.jpg');
        $new = $this->media($editor, 'new.jpg');
        $officer = Officer::create([
            'path' => '/pages/officers/one',
            'name_bn' => 'Officer',
            'photo_media_id' => $old->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $workflow = app(ContentWorkflow::class);
        $revision = $workflow->saveDraft($officer, ['photo_media_id' => $new->id], $editor);
        $this->assertInstanceOf(ContentRevision::class, $revision);

        $workflow->submitRevision($revision, $editor);
        $workflow->publishRevision($revision->fresh(), $publisher);

        $this->assertSame($new->id, $officer->fresh()->photo_media_id);
        $this->assertSoftDeleted('media_files', ['id' => $old->id]);
        Storage::disk('uploads')->assertMissing('testing/old.jpg');
        $this->assertDatabaseHas('media_files', ['id' => $new->id, 'deleted_at' => null]);
    }

    public function test_soft_deleted_officer_keeps_photo_for_restoration_and_force_delete_cleans_it(): void
    {
        Storage::fake('uploads');
        $publisher = User::factory()->create(['role' => 'publisher']);
        $photo = $this->media($publisher, 'officer.jpg');
        $officer = Officer::create([
            'path' => '/pages/officers/restorable', 'name_bn' => 'Restorable', 'status' => 'published',
            'photo_media_id' => $photo->id, 'created_by' => $publisher->id, 'updated_by' => $publisher->id,
        ]);

        $officer->delete();
        app(ContentWorkflow::class)->restoreOfficer($officer, $publisher);
        $this->assertFalse($officer->fresh()->trashed());
        Storage::disk('uploads')->assertExists('testing/officer.jpg');

        $officer->delete();
        app(ContentWorkflow::class)->forceDeleteOfficer($officer, $publisher);
        $this->assertDatabaseMissing('officers', ['id' => $officer->id]);
        $this->assertSoftDeleted('media_files', ['id' => $photo->id]);
        Storage::disk('uploads')->assertMissing('testing/officer.jpg');
    }

    public function test_replacing_an_unsubmitted_draft_cleans_its_abandoned_upload(): void
    {
        Storage::fake('uploads');
        $editor = User::factory()->create(['role' => 'editor']);
        $page = Page::create(['path' => '/pages/live', 'title_bn' => 'Live', 'status' => 'published']);
        $media = $this->media($editor, 'abandoned.pdf');
        ContentRevision::create([
            'revisionable_type' => $page->getMorphClass(),
            'revisionable_id' => $page->id,
            'snapshot' => ['title_bn' => 'Old draft', '_pending_attachments' => [['media_file_id' => $media->id]]],
            'action' => 'draft',
            'is_working' => true,
            'user_id' => $editor->id,
        ]);

        $newRevision = app(ContentWorkflow::class)->saveDraft($page, ['title_bn' => 'New draft'], $editor);

        $this->assertInstanceOf(ContentRevision::class, $newRevision);
        $this->assertSame(1, ContentRevision::where('action', 'draft')->count());
        $this->assertSoftDeleted('media_files', ['id' => $media->id]);
    }

    private function media(User $user, string $name): MediaFile
    {
        $path = 'testing/'.$name;
        Storage::disk('uploads')->put($path, 'test');

        return MediaFile::create([
            'disk' => 'uploads',
            'path' => $path,
            'original_name' => $name,
            'mime_type' => str_ends_with($name, '.jpg') ? 'image/jpeg' : 'application/pdf',
            'size' => 4,
            'uploaded_by' => $user->id,
        ]);
    }
}
