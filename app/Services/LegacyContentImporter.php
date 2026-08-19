<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\ContentType;
use App\Models\GalleryAlbum;
use App\Models\GalleryItem;
use App\Models\ImportRun;
use App\Models\LayoutItem;
use App\Models\LayoutSection;
use App\Models\MediaAttachment;
use App\Models\MediaFile;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Officer;
use App\Models\Page;
use App\Models\Setting;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LegacyContentImporter
{
    private array $counts = [];
    private array $warnings = [];

    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function import(?string $sourcePath = null, ?int $userId = null): ImportRun
    {
        $sourcePath ??= database_path('seeders/data/jdpc.json');
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('The bundled JDPC content file is missing or unreadable.');
        }

        $raw = file_get_contents($sourcePath);
        $hash = hash('sha256', (string) $raw);
        $existing = ImportRun::query()->where('checksum', $hash)->where('status', 'completed')->latest()->first();
        if ($existing) return $existing;

        $data = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        $run = ImportRun::create([
            'source' => basename($sourcePath),
            'checksum' => $hash,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($data, $userId): void {
                $this->importSettings($data);
                $this->importPages($data['pages'] ?? [], $userId);
                $this->importOfficers($data['pages']['/pages/officers']['officers'] ?? [], $userId);
                $this->importMenu($data['menu'] ?? []);
                $this->importHome($data['home'] ?? [], $userId);
                $this->importSidebar($data['sidebar'] ?? []);
                $this->importFooter($data['footer'] ?? []);
            }, 3);

            $run->update([
                'status' => 'completed',
                'counts' => $this->counts,
                'warnings' => $this->warnings ?: null,
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'error' => Str::limit($exception->getMessage(), 65000), 'finished_at' => now()]);
            throw $exception;
        }

        return $run->refresh();
    }

    private function importSettings(array $data): void
    {
        $site = $data['site'] ?? [];
        $settings = [
            'site.portal_title' => [$site['portalTitle'] ?? '', null, 'string'],
            'site.portal_url' => [$site['portalUrl'] ?? '', null, 'url'],
            'site.office_title' => [$site['officeTitle'] ?? '', null, 'string'],
            'site.office_subtitle' => [$site['officeSubtitle'] ?? '', null, 'string'],
            'site.logo' => ['/assets/logo.png', null, 'url'],
            'site.hero_image' => [$site['heroImage'] ?? '', null, 'url'],
            'site.hero_alt' => [$site['heroAlt'] ?? '', null, 'string'],
            'site.last_updated' => [$site['lastUpdated'] ?? '', null, 'string'],
        ];
        foreach ($settings as $key => [$bn, $en, $type]) {
            Setting::updateOrCreate(['key' => $key], ['group' => 'site', 'value_bn' => $this->plain($bn), 'value_en' => $en, 'type' => $type, 'is_public' => true]);
            $this->bump('settings');
        }
    }

    private function importPages(array $pages, ?int $userId): void
    {
        $types = [];
        foreach ($pages as $path => $entry) {
            if (! is_array($entry)) continue;
            $path = $this->path($entry['path'] ?? $path);
            $kind = $entry['kind'] ?? 'raw';
            $segment = explode('/', trim($path, '/'))[1] ?? 'pages';

            if ($kind === 'content') {
                $type = $types[$segment] ??= ContentType::updateOrCreate(
                    ['code' => Str::limit($segment, 64, '')],
                    [
                        'name_bn' => $this->typeName($segment),
                        'name_en' => Str::headline($segment),
                        'base_path' => '/pages/'.$segment,
                        'fields' => ['title', 'body', 'summary', 'attachments', 'metadata'],
                        'columns' => ['title', 'published_at', 'status'],
                        'is_active' => true,
                    ]
                );
                $item = ContentItem::updateOrCreate(['path' => $path], [
                    'content_type_id' => $type->id,
                    'slug' => Str::limit(basename($path), 255, ''),
                    'title_bn' => $this->plain($entry['title'] ?? 'শিরোনামহীন'),
                    'body_bn' => $this->sanitizer->clean($entry['html'] ?? ''),
                    'status' => 'published',
                    'published_at' => now(),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'published_by' => $userId,
                    'metadata' => array_filter([
                        'legacy_updated' => $this->plain($entry['updated'] ?? ''),
                        'chips' => $this->plainArray($entry['chips'] ?? []),
                    ]),
                ]);
                $this->syncAttachments($item, $entry, $userId);
                $this->bump('content_items');
                continue;
            }

            if ($kind === 'officers') {
                Page::updateOrCreate(['path' => $path], [
                    'slug' => 'officers', 'template' => 'officers', 'title_bn' => $this->plain($entry['title'] ?? 'কর্মকর্তাবৃন্দ'),
                    'status' => 'published', 'published_at' => now(), 'created_by' => $userId, 'updated_by' => $userId, 'published_by' => $userId,
                ]);
                $this->bump('pages');
                continue;
            }
            if ($segment === 'officers' && str_contains($path, '/officers/')) continue;

            Page::updateOrCreate(['path' => $path], [
                'slug' => Str::limit(basename($path), 255, ''),
                'template' => in_array($kind, ['table', 'raw'], true) ? $kind : 'content',
                'title_bn' => $this->plain($entry['title'] ?? 'শিরোনামহীন'),
                'body_bn' => $this->sanitizer->clean($entry['html'] ?? ''),
                'status' => 'published',
                'published_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
                'published_by' => $userId,
                'metadata' => array_filter([
                    'columns' => $this->plainArray($entry['columns'] ?? []),
                    'rows' => $this->cleanTableRows($entry['rows'] ?? []),
                ]),
            ]);
            $this->bump('pages');
        }
        $this->counts['content_types'] = count($types);
    }

    private function importOfficers(array $officers, ?int $userId): void
    {
        foreach ($officers as $position => $source) {
            $path = $this->path($source['href'] ?? '/pages/officers/officer-'.($position + 1));
            Officer::updateOrCreate(['path' => $path], [
                'name_bn' => $this->plain($source['নাম'] ?? 'নাম নেই'),
                'designation_bn' => $this->plain($source['পদবি'] ?? ''),
                'office_bn' => $this->plain($source['অফিস'] ?? ''),
                'email' => filter_var($source['ইমেইল'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
                'office_phone' => $this->plain($source['ফোন (অফিস)'] ?? ''),
                'intercom' => $this->plain($source['ইন্টারকম'] ?? ''),
                'room' => $this->plain($source['কক্ষ নম্বর'] ?? ''),
                'mobile' => $this->plain($source['মোবাইল'] ?? ''),
                'fax' => $this->plain($source['ফ্যাক্স'] ?? ''),
                'photo_url' => $this->url($source['photo'] ?? ''),
                'sort_order' => $position,
                'status' => 'published',
                'created_by' => $userId,
                'updated_by' => $userId,
                'published_by' => $userId,
            ]);
            $this->bump('officers');
        }
    }

    private function importMenu(array $source): void
    {
        $menu = Menu::updateOrCreate(['location' => 'primary'], ['name_bn' => 'প্রধান মেনু', 'name_en' => 'Primary menu', 'is_active' => true]);
        MenuItem::query()->where('menu_id', $menu->id)->whereNull('parent_id')->delete();
        foreach ($source as $order => $top) {
            $parent = $this->makeMenuItem($menu, null, $top['label'] ?? '', $top['href'] ?? '#', $order);
            foreach (($top['groups'] ?? []) as $groupOrder => $group) {
                $groupParent = $parent;
                if (filled($group['title'] ?? null)) {
                    $groupParent = $this->makeMenuItem($menu, $parent, $group['title'], '#', $groupOrder);
                }
                foreach (($group['items'] ?? []) as $itemOrder => $item) {
                    $this->makeMenuItem($menu, $groupParent, $item['label'] ?? '', $item['href'] ?? '#', $itemOrder);
                }
            }
        }
        $this->counts['menu_items'] = MenuItem::query()->where('menu_id', $menu->id)->count();
    }

    private function makeMenuItem(Menu $menu, ?MenuItem $parent, string $label, string $url, int $order): MenuItem
    {
        $url = $this->url($url, true);
        $page = str_starts_with($url, '/') ? Page::query()->where('path', $url)->first() : null;
        $content = ! $page && str_starts_with($url, '/') ? ContentItem::query()->where('path', $url)->first() : null;
        return MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parent?->id,
            'label_bn' => $this->plain($label),
            'url' => $page || $content ? null : $url,
            'page_id' => $page?->id,
            'content_item_id' => $content?->id,
            'target' => str_starts_with($url, 'http') ? '_blank' : '_self',
            'sort_order' => $order,
            'is_active' => true,
        ]);
    }

    private function importHome(array $home, ?int $userId): void
    {
        foreach (($home['notices'] ?? []) as $notice) {
            $item = ContentItem::query()->where('path', $this->path($notice['href'] ?? ''))->first();
            if ($item) {
                $item->metadata = array_merge($item->metadata ?? [], [
                    'display_date' => $this->plain($notice['date'] ?? ''),
                    'tag' => $this->plain($notice['tag'] ?? ''),
                ]);
                $item->save();
            }
        }

        $photos = GalleryAlbum::updateOrCreate(['path' => '/photo-gallery/home'], [
            'title_bn' => 'ফটো গ্যালারি', 'title_en' => 'Photo gallery', 'status' => 'published',
            'published_at' => now(), 'created_by' => $userId, 'updated_by' => $userId, 'published_by' => $userId,
        ]);
        $photos->items()->delete();
        foreach (($home['photos'] ?? []) as $order => $photo) {
            GalleryItem::create([
                'gallery_album_id' => $photos->id,
                'image_url' => $this->url($photo['src'] ?? ''),
                'caption_bn' => $this->plain($photo['caption'] ?? ''),
                'sort_order' => $order,
            ]);
            $this->bump('gallery_items');
        }

        $section = $this->replaceSection('home', 'service-boxes', 'service_boxes', 'সেবা বক্স', 20, ['all_href' => $this->url($home['serviceBoxes']['allHref'] ?? '', true)]);
        foreach (($home['serviceBoxes']['boxes'] ?? []) as $order => $box) {
            $parent = LayoutItem::create([
                'layout_section_id' => $section->id,
                'label_bn' => $this->plain($box['title'] ?? ''),
                'media_url' => $this->url($box['image'] ?? ''),
                'sort_order' => $order,
                'is_active' => true,
            ]);
            foreach (($box['links'] ?? []) as $linkOrder => $link) {
                LayoutItem::create([
                    'layout_section_id' => $section->id,
                    'parent_id' => $parent->id,
                    'label_bn' => $this->plain($link['label'] ?? ''),
                    'url' => $this->url($link['href'] ?? '#', true),
                    'sort_order' => $linkOrder,
                    'is_active' => true,
                ]);
            }
        }

        $blocks = $this->replaceSection('home', 'content-blocks', 'rich_text', 'কনটেন্ট ব্লক', 30);
        foreach (($home['blocks'] ?? []) as $order => $block) {
            LayoutItem::create([
                'layout_section_id' => $blocks->id,
                'label_bn' => $this->plain($block['title'] ?? ''),
                'text_bn' => $this->sanitizer->clean($block['html'] ?? ''),
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }

        $contact = $home['getInTouch'] ?? [];
        $section = $this->replaceSection('home', 'contact', 'contact', $contact['title'] ?? 'যোগাযোগের ঠিকানা', 40, [
            'office' => $this->plain($contact['office'] ?? ''),
            'map_src' => $this->url($contact['mapSrc'] ?? ''),
            'map_title' => $this->plain($contact['mapTitle'] ?? ''),
            'social_links' => [['label' => 'Facebook', 'url' => 'https://www.facebook.com/jdpcbd/', 'icon' => 'ph-fill ph-facebook-logo', 'color' => '#3b5998']],
        ]);
        foreach (($contact['items'] ?? []) as $order => $item) {
            LayoutItem::create([
                'layout_section_id' => $section->id,
                'label_bn' => $this->plain($item['label'] ?? ''),
                'text_bn' => $this->plain($item['value'] ?? ''),
                'icon' => $this->plain($item['icon'] ?? ''),
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }

        Setting::updateOrCreate(['key' => 'home.news_ticker'], [
            'group' => 'home', 'type' => 'json', 'value_bn' => json_encode($this->cleanLinks($home['news'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'is_public' => true,
        ]);
    }

    private function importSidebar(array $widgets): void
    {
        $section = $this->replaceSection('global', 'sidebar', 'sidebar', 'সাইডবার', 10);
        foreach ($widgets as $order => $widget) {
            LayoutItem::create([
                'layout_section_id' => $section->id,
                'label_bn' => $this->plain($widget['title'] ?? ''),
                'text_bn' => $this->sanitizer->clean($widget['html'] ?? ''),
                'url' => $this->url($widget['href'] ?? '', true),
                'settings' => [
                    'widget_type' => $this->plain($widget['type'] ?? 'BlockWidget'),
                    'items' => $this->cleanLinks($widget['items'] ?? []),
                    'all_href' => $this->url($widget['allHref'] ?? '', true),
                ],
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }
        $this->counts['sidebar_widgets'] = count($widgets);
    }

    private function importFooter(array $footer): void
    {
        Setting::updateOrCreate(['key' => 'footer.text'], ['group' => 'footer', 'type' => 'text', 'value_bn' => $this->plain($footer['text'] ?? ''), 'is_public' => true]);
        Setting::updateOrCreate(['key' => 'footer.links'], [
            'group' => 'footer', 'type' => 'json', 'value_bn' => json_encode($this->cleanLinks($footer['links'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'is_public' => true,
        ]);
        Setting::updateOrCreate(['key' => 'footer.html'], ['group' => 'footer', 'type' => 'html', 'value_bn' => $this->sanitizer->clean($footer['html'] ?? ''), 'is_public' => true]);
        Setting::updateOrCreate(['key' => 'footer.credit'], ['group' => 'footer', 'type' => 'text', 'value_bn' => 'পরিকল্পনা ও বাস্তবায়নে মন্ত্রিপরিষদ বিভাগ, এটুআই, বিসিসি, ডিওআইসিটি ও বেসিস।', 'value_en' => 'Planned and implemented by Cabinet Division, a2i, BCC, DoICT and BASIS.', 'is_public' => true]);
        Setting::updateOrCreate(['key' => 'footer.support_label'], ['group' => 'footer', 'type' => 'string', 'value_bn' => 'কারিগরি সহায়তায়', 'value_en' => 'Technical support', 'is_public' => true]);
        Setting::updateOrCreate(['key' => 'footer.support_image'], ['group' => 'footer', 'type' => 'url', 'value_bn' => '/assets/technical-support.svg', 'is_public' => true]);
        $this->bump('settings', 6);
    }

    private function replaceSection(string $region, string $key, string $type, string $title, int $order, array $settings = []): LayoutSection
    {
        $section = LayoutSection::updateOrCreate(['area' => $region, 'key' => $key], [
            'type' => $type, 'title_bn' => $this->plain($title), 'settings' => $settings, 'sort_order' => $order, 'is_active' => true,
        ]);
        $section->items()->withoutGlobalScopes()->delete();
        $this->bump('layout_sections');
        return $section;
    }

    private function syncAttachments(ContentItem $item, array $entry, ?int $userId): void
    {
        $item->attachments()->delete();
        foreach (['images' => 'image', 'files' => 'attachment'] as $key => $role) {
            foreach (($entry[$key] ?? []) as $order => $source) {
                $url = $this->url($source['src'] ?? $source['href'] ?? '');
                if (! $url) continue;
                $media = $this->remoteMedia($url, $source['label'] ?? $source['alt'] ?? '', $userId);
                MediaAttachment::create([
                    'media_file_id' => $media->id,
                    'attachable_type' => $item->getMorphClass(),
                    'attachable_id' => $item->id,
                    'role' => $role,
                    'label_bn' => $this->plain($source['label'] ?? ''),
                    'sort_order' => $order,
                ]);
            }
        }
    }

    private function remoteMedia(string $url, string $alt, ?int $userId): MediaFile
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $filename = Str::limit(basename($path) ?: 'remote-file', 255, '');
        return MediaFile::updateOrCreate(['remote_url' => $url], [
            'disk' => 'remote',
            'original_name' => $filename,
            'mime_type' => $this->mimeFromPath($path),
            'alt_bn' => $this->plain($alt),
            'uploaded_by' => $userId,
        ]);
    }

    private function cleanTableRows(array $rows): array
    {
        return array_map(fn ($row) => array_map(fn ($cell) => [
            'text' => $this->plain($cell['text'] ?? ''),
            'href' => $this->url($cell['href'] ?? '', true),
            'img' => $this->url($cell['img'] ?? ''),
        ], is_array($row) ? $row : []), $rows);
    }

    private function cleanLinks(array $links): array
    {
        return array_map(fn ($item) => [
            'label' => $this->plain($item['label'] ?? $item['title'] ?? ''),
            'title' => $this->plain($item['title'] ?? ''),
            'href' => $this->url($item['href'] ?? '', true),
            'icon' => $this->plain($item['icon'] ?? ''),
        ], $links);
    }

    private function plainArray(array $values): array
    {
        return array_map(fn ($value) => is_scalar($value) ? $this->plain((string) $value) : '', $values);
    }

    private function plain(mixed $value): string
    {
        return trim(Str::limit(strip_tags(is_scalar($value) ? (string) $value : ''), 65000, ''));
    }

    private function path(string $path): string
    {
        $path = '/'.ltrim(trim($path), '/');
        return Str::limit($path, 512, '');
    }

    private function url(mixed $url, bool $relative = false): string
    {
        if (! is_string($url)) return '';
        $url = trim($url);
        if ($url === '' || $url === '#') return $url;
        if ($relative && str_starts_with($url, '/')) return Str::limit($url, 1024, '');
        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) return Str::limit($url, 1024, '');
        if ($relative && (str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:'))) return Str::limit($url, 1024, '');
        $this->warnings[] = 'Skipped an unsafe URL.';
        return '';
    }

    private function typeName(string $segment): string
    {
        return [
            'forms' => 'ফরম', 'internal-eservices' => 'অভ্যন্তরীণ ই-সেবা', 'laws' => 'আইন', 'news' => 'খবর',
            'notices' => 'নোটিশ', 'notification-circulars' => 'প্রজ্ঞাপন/পরিপত্র', 'office-orders' => 'অফিস আদেশ',
            'policies' => 'নীতিমালা', 'projects' => 'প্রকল্প', 'static-pages' => 'সাধারণ কনটেন্ট', 'tenders' => 'দরপত্র',
        ][$segment] ?? Str::headline($segment);
    }

    private function mimeFromPath(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf', 'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', default => null,
        };
    }

    private function bump(string $key, int $by = 1): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + $by;
    }
}
