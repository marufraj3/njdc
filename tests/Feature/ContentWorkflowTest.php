<?php

namespace Tests\Feature;

use App\Models\ContentRevision;
use App\Models\Page;
use App\Models\User;
use App\Services\ContentWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_revision_keeps_published_page_live_until_publisher_approval(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/test', 'লাইভ');
        $workflow = app(ContentWorkflow::class);

        $revision = $workflow->saveDraft($page, ['title_bn' => 'পরিবর্তিত'], $editor);

        $this->assertInstanceOf(ContentRevision::class, $revision);
        $this->assertTrue($revision->is_working);
        $this->assertSame((string) $page->getRawOriginal('updated_at'), $revision->snapshot['_base_updated_at']);
        $this->assertSame('লাইভ', $page->fresh()->title_bn);

        $workflow->submitRevision($revision, $editor);
        $workflow->publishRevision($revision->fresh(), $publisher);

        $this->assertSame('পরিবর্তিত', $page->fresh()->title_bn);
        $this->assertSame('published', $page->fresh()->status);
        $this->assertFalse($revision->fresh()->is_working);
    }

    public function test_publisher_save_does_not_unpublish_live_content(): void
    {
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/live', 'আগে');

        $result = app(ContentWorkflow::class)->saveDraft($page, ['title_bn' => 'এখন'], $publisher);

        $this->assertInstanceOf(Page::class, $result);
        $this->assertSame('published', $result->fresh()->status);
        $this->assertSame('এখন', $result->fresh()->title_bn);
        $this->assertFalse(ContentRevision::latest('id')->firstOrFail()->is_working);
    }

    public function test_editor_cannot_replace_a_submitted_working_revision(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/submitted', 'লাইভ');
        $workflow = app(ContentWorkflow::class);
        $revision = $workflow->saveDraft($page, ['title_bn' => 'প্রথম'], $editor);
        $workflow->submitRevision($revision, $editor);

        try {
            $workflow->saveDraft($page, ['title_bn' => 'দ্বিতীয়'], $editor);
            $this->fail('A submitted revision was replaced.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(1, ContentRevision::query()->where('is_working', true)->count());
        $this->assertSame('submitted', $revision->fresh()->action);
        $this->assertSame('প্রথম', $revision->fresh()->snapshot['title_bn']);
    }

    public function test_rejected_revision_must_be_replaced_with_a_fresh_draft(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/rejected', 'লাইভ');
        $workflow = app(ContentWorkflow::class);
        $rejected = $workflow->saveDraft($page, ['title_bn' => 'প্রত্যাখ্যাত'], $editor);
        $workflow->submitRevision($rejected, $editor);
        $workflow->reject($rejected->fresh(), $publisher, 'আবার লিখুন');

        $this->expectException(ValidationException::class);
        $workflow->submitRevision($rejected->fresh(), $editor);
    }

    public function test_rejected_revision_view_links_to_fresh_edit_instead_of_direct_resubmission(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/rejected-view', 'লাইভ');
        $workflow = app(ContentWorkflow::class);
        $revision = $workflow->saveDraft($page, ['title_bn' => 'প্রত্যাখ্যাত'], $editor);
        $workflow->submitRevision($revision, $editor);
        $workflow->reject($revision->fresh(), $publisher, 'আবার লিখুন');

        $this->actingAs($editor)->get(route('admin.revisions.show', $revision))
            ->assertOk()
            ->assertSee(route('admin.pages.edit', $page), false)
            ->assertDontSee(route('admin.revisions.submit', $revision), false);
    }

    public function test_editing_after_rejection_supersedes_it_and_creates_a_clean_draft(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/replacement', 'লাইভ');
        $workflow = app(ContentWorkflow::class);
        $rejected = $workflow->saveDraft($page, ['title_bn' => 'প্রত্যাখ্যাত'], $editor);
        $workflow->submitRevision($rejected, $editor);
        $workflow->reject($rejected->fresh(), $publisher, 'আবার লিখুন');

        $replacement = $workflow->saveDraft($page->fresh(), ['title_bn' => 'নতুন খসড়া'], $editor);

        $this->assertInstanceOf(ContentRevision::class, $replacement);
        $this->assertSame('draft', $replacement->action);
        $this->assertTrue($replacement->is_working);
        $this->assertSame('নতুন খসড়া', $replacement->snapshot['title_bn']);
        $this->assertSame('superseded', $rejected->fresh()->action);
        $this->assertFalse($rejected->fresh()->is_working);
        $this->actingAs($editor)
            ->get(route('admin.revisions.index', ['action' => 'superseded']))
            ->assertOk()
            ->assertSee(route('admin.revisions.show', $rejected), false);
    }

    public function test_publisher_cannot_approve_a_revision_based_on_stale_live_content(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/stale', 'মূল লেখা');
        $workflow = app(ContentWorkflow::class);
        $revision = $workflow->saveDraft($page, ['title_bn' => 'সম্পাদকের লেখা'], $editor);
        $workflow->submitRevision($revision, $editor);

        Page::query()->whereKey($page->getKey())->update([
            'title_bn' => 'অন্য প্রকাশিত পরিবর্তন',
            'updated_at' => now()->addMinute(),
        ]);

        try {
            $workflow->publishRevision($revision->fresh(), $publisher);
            $this->fail('A stale revision was published.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame('অন্য প্রকাশিত পরিবর্তন', $page->fresh()->title_bn);
        $this->assertSame('submitted', $revision->fresh()->action);
        $this->assertTrue($revision->fresh()->is_working);
    }

    public function test_publisher_cannot_approve_a_revision_without_base_version_metadata(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $publisher = User::factory()->create(['role' => 'publisher', 'is_active' => true]);
        $page = $this->publishedPage($publisher, '/pages/missing-base-version', 'লাইভ');
        $workflow = app(ContentWorkflow::class);
        $revision = $workflow->saveDraft($page, ['title_bn' => 'পরিবর্তিত'], $editor);
        $snapshot = $revision->snapshot;
        unset($snapshot['_base_updated_at']);
        $revision->forceFill(['snapshot' => $snapshot])->save();
        $workflow->submitRevision($revision->fresh(), $editor);

        try {
            $workflow->publishRevision($revision->fresh(), $publisher);
            $this->fail('A revision without base-version metadata was published.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame('লাইভ', $page->fresh()->title_bn);
        $this->assertSame('submitted', $revision->fresh()->action);
        $this->assertTrue($revision->fresh()->is_working);
    }

    private function publishedPage(User $publisher, string $path, string $title): Page
    {
        return Page::create([
            'path' => $path,
            'slug' => basename($path),
            'title_bn' => $title,
            'status' => 'published',
            'created_by' => $publisher->id,
            'updated_by' => $publisher->id,
            'published_by' => $publisher->id,
            'published_at' => now(),
        ]);
    }
}
