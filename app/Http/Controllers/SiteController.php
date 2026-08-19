<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\GalleryAlbum;
use App\Models\LayoutSection;
use App\Models\Menu;
use App\Models\Officer;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home(Request $request)
    {
        $data = $this->shell();
        $data += [
            'notices' => $this->items('notices', 5),
            'news' => Setting::valueFor('home.news_ticker', app()->getLocale(), []),
            'gallery' => GalleryAlbum::published()->where('path', '/photo-gallery/home')->with(['items' => fn ($query) => $query->where('is_active', true)->where(fn ($dates) => $dates->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>=', now()))])->first(),
            'homeSections' => LayoutSection::query()->where('area', 'home')->where('is_active', true)->with(['items.children'])->orderBy('sort_order')->get()->keyBy('key'),
        ];
        return view('site.home', $data);
    }

    public function show(Request $request, ?string $path = null)
    {
        $requestedPath = '/'.ltrim((string) $path, '/');
        $paths = [$requestedPath];
        if (! str_starts_with($requestedPath, '/pages/')) $paths[] = '/pages/'.ltrim($requestedPath, '/');

        foreach ($paths as $candidate) {
            if ($redirect = Redirect::query()->where('source_path', $candidate)->where('is_active', true)->first()) {
                return redirect($redirect->destination, $redirect->status_code);
            }
        }

        $record = null;
        foreach ($paths as $candidate) {
            $record = Officer::published()->where('path', $candidate)->first()
                ?? ContentItem::published()->with(['type', 'attachments.media'])->where('path', $candidate)->first()
                ?? Page::published()->with('attachments.media')->where('path', $candidate)->first()
                ?? GalleryAlbum::published()->with(['items' => fn ($query) => $query
                    ->where('is_active', true)
                    ->where(fn ($dates) => $dates->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                    ->orderBy('sort_order')])
                    ->where('path', $candidate)->first();
            if ($record) break;
        }

        abort_unless($record, 404);
        return view('site.page', $this->shell() + [
            'record' => $record,
            'officers' => $record instanceof Page && $record->template === 'officers'
                ? Officer::published()->orderBy('sort_order')->get()
                : collect(),
        ]);
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q'));
        $results = collect();
        if (mb_strlen($term) >= 2) {
            $field = app()->getLocale() === 'en' ? 'title_en' : 'title_bn';
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
            $pages = Page::published()->where($field, 'like', "%{$escaped}%")->limit(25)->get();
            $items = ContentItem::published()->where($field, 'like', "%{$escaped}%")->limit(25)->get();
            $results = $pages->concat($items)->sortByDesc('published_at')->values();
        }
        return view('site.search', $this->shell() + ['results' => $results, 'term' => $term]);
    }

    private function shell(): array
    {
        return [
            'siteSettings' => Setting::query()->where('group', 'site')->get()->keyBy('key'),
            'primaryMenu' => Menu::query()->where('location', 'primary')->where('is_active', true)->with(['items.children.children', 'items.page', 'items.contentItem'])->first(),
            'sidebar' => LayoutSection::query()->where('area', 'global')->where('key', 'sidebar')->where('is_active', true)->with(['items.children'])->first(),
            'footerText' => Setting::valueFor('footer.text'),
            'footerLinks' => Setting::valueFor('footer.links', app()->getLocale(), []),
            'footerCredit' => Setting::valueFor('footer.credit') ?: __('Planned and implemented by Cabinet Division, a2i, BCC, DoICT and BASIS.'),
            'footerSupportLabel' => Setting::valueFor('footer.support_label') ?: __('Technical support'),
            'footerSupportImage' => Setting::valueFor('footer.support_image') ?: '/assets/technical-support.svg',
        ];
    }

    private function items(string $code, int $limit)
    {
        return ContentItem::published()->whereHas('type', fn ($query) => $query->where('code', $code))->latest('published_at')->limit($limit)->get();
    }
}
