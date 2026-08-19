<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\User;
use App\Rules\PublicPathAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PublicPathValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_paths_are_unique_across_all_public_record_types_including_trash(): void
    {
        $page = Page::create(['path' => '/pages/shared', 'title_bn' => 'Shared', 'status' => 'draft']);
        $page->delete();

        $officerValidator = Validator::make(
            ['path' => '/pages/shared'],
            ['path' => [new PublicPathAvailable('officers')]],
        );
        $this->assertTrue($officerValidator->fails());

        GalleryAlbum::create(['path' => '/photo-gallery/shared', 'title_bn' => 'Gallery', 'status' => 'draft']);
        $pageValidator = Validator::make(
            ['path' => '/photo-gallery/shared'],
            ['path' => [new PublicPathAvailable('pages')]],
        );
        $this->assertTrue($pageValidator->fails());
    }

    public function test_page_paths_are_normalized_before_validation_and_persistence(): void
    {
        $publisher = User::factory()->create(['role' => 'publisher']);

        $this->actingAs($publisher)->post(route('admin.pages.store'), [
            'path' => '///pages//about///',
            'template' => 'content',
            'title_bn' => 'About',
            'is_indexable' => 1,
        ])->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('pages', ['path' => '/pages/about']);
    }

    public function test_reserved_and_malformed_public_paths_are_rejected(): void
    {
        foreach (['/', '/admin/users', '/en/pages/about', '/pages/bad?x=1', '/pages/bad path'] as $path) {
            $this->assertTrue(Validator::make(
                ['path' => $path],
                ['path' => [new PublicPathAvailable('pages')]],
            )->fails(), $path.' should have failed validation.');
        }
    }
}
