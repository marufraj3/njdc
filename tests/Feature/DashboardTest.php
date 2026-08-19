<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\ContentRevision;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_review_list_only_contains_active_submitted_revisions(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $page = Page::create(['path' => '/pages/dashboard', 'title_bn' => 'Dashboard', 'status' => 'published']);
        $active = $this->revision($page, $editor, true);
        $this->revision($page, $editor, false);

        $view = app(DashboardController::class)();
        $revisions = $view->getData()['revisions'];

        $this->assertSame([$active->id], $revisions->pluck('id')->all());
    }

    private function revision(Page $page, User $editor, bool $isWorking): ContentRevision
    {
        return ContentRevision::create([
            'revisionable_type' => $page->getMorphClass(),
            'revisionable_id' => $page->id,
            'snapshot' => ['title_bn' => 'Changed'],
            'action' => 'submitted',
            'is_working' => $isWorking,
            'user_id' => $editor->id,
        ]);
    }
}
