<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\LayoutItem;
use App\Models\LayoutSection;
use App\Models\MediaFile;
use App\Rules\SafeUrl;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LayoutController extends Controller
{
    public function index(Request $request)
    {
        $layouts = LayoutSection::orderBy('area')->orderBy('sort_order')->get();
        $selected = $request->layout ? LayoutSection::find($request->layout) : $layouts->first();

        return view('admin.layouts.index', [
            'layouts' => $layouts,
            'selected' => $selected,
            'selectedItems' => $selected
                ? LayoutItem::where('layout_section_id', $selected->id)->orderBy('sort_order')->get()
                : collect(),
            'media' => MediaFile::latest()->limit(200)->get(),
            'content' => ContentItem::published()->orderBy('title_bn')->get(['id', 'title_bn']),
        ]);
    }

    public function store(Request $request, HtmlSanitizer $sanitizer)
    {
        LayoutSection::create($this->sectionData($request, $sanitizer));
        return back()->with('status', 'সেকশন তৈরি হয়েছে।');
    }

    public function update(Request $request, LayoutSection $layout, HtmlSanitizer $sanitizer)
    {
        $layout->update($this->sectionData($request, $sanitizer, $layout));
        return back()->with('status', 'সেকশন হালনাগাদ হয়েছে।');
    }

    public function storeItem(Request $request, LayoutSection $layout, HtmlSanitizer $sanitizer)
    {
        $data = $this->itemData($request, $layout, $sanitizer);
        $data['layout_section_id'] = $layout->id;
        LayoutItem::create($data);
        return back()->with('status', 'উইজেট আইটেম যোগ হয়েছে।');
    }

    public function updateItem(Request $request, LayoutItem $item, HtmlSanitizer $sanitizer)
    {
        $item->update($this->itemData($request, $item->section, $sanitizer, $item));
        return back()->with('status', 'উইজেট আইটেম হালনাগাদ হয়েছে।');
    }

    public function destroyItem(LayoutItem $item)
    {
        $item->delete();
        return back()->with('status', 'আইটেম মুছে ফেলা হয়েছে।');
    }

    private function sectionData(Request $request, HtmlSanitizer $sanitizer, ?LayoutSection $section = null): array
    {
        $data = $request->validate([
            'area' => 'required|max:32',
            'key' => ['required', 'max:64', Rule::unique('layout_sections')->where('area', $request->area)->ignore($section)],
            'type' => 'required|max:64',
            'title_bn' => 'nullable|max:255',
            'title_en' => 'nullable|max:255',
            'body_bn' => 'nullable|max:1000000',
            'body_en' => 'nullable|max:1000000',
            'url' => ['nullable', 'max:2000', new SafeUrl],
            'media_url' => ['nullable', 'max:2000', new SafeUrl],
            'settings' => 'nullable|json|max:1000000',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['body_bn'] = $sanitizer->clean($data['body_bn'] ?? '');
        $data['body_en'] = $sanitizer->clean($data['body_en'] ?? '');
        $data['settings'] = filled($data['settings'] ?? null) ? json_decode($data['settings'], true) : null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function itemData(Request $request, LayoutSection $section, HtmlSanitizer $sanitizer, ?LayoutItem $item = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', Rule::exists('layout_items', 'id')->where('layout_section_id', $section->id)],
            'content_item_id' => 'nullable|exists:content_items,id',
            'media_file_id' => 'nullable|exists:media_files,id',
            'label_bn' => 'nullable|max:255',
            'label_en' => 'nullable|max:255',
            'text_bn' => 'nullable|max:1000000',
            'text_en' => 'nullable|max:1000000',
            'url' => ['nullable', 'max:2000', new SafeUrl],
            'media_url' => ['nullable', 'max:2000', new SafeUrl],
            'icon' => 'nullable|max:128',
            'settings' => 'nullable|json|max:1000000',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($item && filled($data['parent_id'] ?? null)) {
            $parentId = (int) $data['parent_id'];
            if ($parentId === $item->id || $this->descendantIds($item)->contains($parentId)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'নিজের বা অধস্তন কোনো আইটেমকে প্যারেন্ট করা যাবে না।',
                ]);
            }
        }

        $data['text_bn'] = $sanitizer->clean($data['text_bn'] ?? '');
        $data['text_en'] = $sanitizer->clean($data['text_en'] ?? '');
        $data['settings'] = filled($data['settings'] ?? null) ? json_decode($data['settings'], true) : null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function descendantIds(LayoutItem $item)
    {
        $descendants = collect();
        $frontier = collect([$item->id]);

        while ($frontier->isNotEmpty()) {
            $frontier = LayoutItem::query()
                ->where('layout_section_id', $item->layout_section_id)
                ->whereIn('parent_id', $frontier)
                ->pluck('id');
            $newIds = $frontier->diff($descendants);
            if ($newIds->isEmpty()) {
                break;
            }
            $descendants = $descendants->merge($newIds);
            $frontier = $newIds;
        }

        return $descendants;
    }
}
