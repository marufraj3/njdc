<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\GalleryItem;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_renders_in_bengali_and_english_routes(): void
    {
        Page::create([
            'path' => '/pages/about',
            'slug' => 'about',
            'template' => 'content',
            'title_bn' => 'আমাদের সম্পর্কে',
            'title_en' => 'About us',
            'body_bn' => '<p>বাংলা লেখা</p>',
            'body_en' => '<p>English copy</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/pages/about')->assertOk()->assertSee('আমাদের সম্পর্কে')->assertSee('বাংলা লেখা');
        $this->get('/en/pages/about')->assertOk()->assertSee('About us')->assertSee('English copy');
    }

    public function test_published_gallery_uses_exact_non_localized_resource_urls(): void
    {
        $gallery = GalleryAlbum::create([
            'path' => '/photo-gallery/events',
            'title_bn' => 'অনুষ্ঠান',
            'title_en' => 'Events',
            'status' => 'published',
            'published_at' => now(),
        ]);
        GalleryItem::create([
            'gallery_album_id' => $gallery->id,
            'image_url' => '/assets/gallery.jpg',
            'caption_bn' => 'ছবি',
            'caption_en' => 'Photo',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->get('/photo-gallery/events')
            ->assertOk()
            ->assertSee('অনুষ্ঠান')
            ->assertSee('src="/assets/gallery.jpg"', false);
        $this->get('/en/photo-gallery/events')
            ->assertOk()
            ->assertSee('Events')
            ->assertSee('Photo')
            ->assertSee('src="/assets/gallery.jpg"', false)
            ->assertDontSee('src="/en/assets/gallery.jpg"', false)
            ->assertDontSee('href="/en/assets/gallery.jpg"', false);
    }

    public function test_english_route_localizes_navigation_but_not_home_gallery_assets(): void
    {
        $menu = Menu::create([
            'location' => 'primary',
            'name_bn' => 'প্রধান',
            'name_en' => 'Primary',
            'is_active' => true,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'label_bn' => 'পরিচিতি',
            'label_en' => 'About',
            'url' => '/pages/about',
            'is_active' => true,
        ]);
        $gallery = GalleryAlbum::create([
            'path' => '/photo-gallery/home',
            'title_bn' => 'হোম গ্যালারি',
            'title_en' => 'Home gallery',
            'status' => 'published',
            'published_at' => now(),
        ]);
        GalleryItem::create([
            'gallery_album_id' => $gallery->id,
            'image_url' => '/uploads/home/slide.jpg',
            'caption_en' => 'Slide',
            'is_active' => true,
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('href="/en/pages/about"', false)
            ->assertSee('src="/uploads/home/slide.jpg"', false)
            ->assertDontSee('/en/uploads/home/slide.jpg', false);
    }

    public function test_search_routes_receive_the_query_without_route_parameter_shifting(): void
    {
        Page::create([
            'path' => '/pages/searchable',
            'title_bn' => 'অনুসন্ধানযোগ্য নথি',
            'title_en' => 'Searchable document',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/search?q='.urlencode('অনুসন্ধানযোগ্য'))->assertOk()->assertSee('অনুসন্ধানযোগ্য নথি');
        $this->get('/en/search?q=Searchable')->assertOk()->assertSee('Searchable document');
    }

    public function test_unsafe_dynamic_setting_urls_are_neutralized_when_rendered(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.hero_image', 'type' => 'string', 'value_bn' => 'javascript:alert(1)', 'value_en' => '//evil.example/banner']);

        $this->get('/')->assertOk()->assertDontSee('javascript:alert', false);
        $this->get('/en')->assertOk()->assertDontSee('//evil.example', false);
    }

    public function test_draft_page_is_not_public(): void
    {
        Page::create(['path' => '/pages/private', 'slug' => 'private', 'template' => 'content', 'title_bn' => 'খসড়া', 'status' => 'draft']);

        $this->get('/pages/private')->assertNotFound();
    }
}
