<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryItem;
use App\Models\MediaFile;
use App\Rules\PublicPathAvailable;
use App\Rules\SafeUrl;
use App\Services\ContentWorkflow;
use App\Services\MediaManager;
use App\Support\PublicPath;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $galleries = GalleryAlbum::orderBy('sort_order')->get();
        $selected = $request->gallery ? GalleryAlbum::find($request->gallery) : $galleries->first();

        return view('admin.galleries.index', [
            'galleries' => $galleries,
            'selected' => $selected,
            'selectedItems' => $selected
                ? GalleryItem::with('media')->where('gallery_album_id', $selected->id)->orderBy('sort_order')->get()
                : collect(),
            'media' => MediaFile::where('mime_type', 'like', 'image/%')->latest()->limit(200)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->albumData($request);
        $data += [
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'published_by' => $request->user()->id,
        ];
        $gallery = GalleryAlbum::create($data);

        return redirect()->route('admin.galleries.index', ['gallery' => $gallery->id])
            ->with('status', 'গ্যালারি তৈরি হয়েছে।');
    }

    public function update(Request $request, GalleryAlbum $gallery)
    {
        $data = $this->albumData($request, $gallery);
        $data['updated_by'] = $request->user()->id;
        if ($data['status'] === 'published' && $gallery->status !== 'published') {
            $data['published_at'] = now();
            $data['published_by'] = $request->user()->id;
        }
        $gallery->update($data);

        return back()->with('status', 'গ্যালারি হালনাগাদ হয়েছে।');
    }

    public function destroy(GalleryAlbum $gallery)
    {
        $gallery->delete();

        return redirect()->route('admin.galleries.index')->with('status', 'গ্যালারি ট্র্যাশে পাঠানো হয়েছে।');
    }

    public function storeItem(Request $request, GalleryAlbum $gallery, MediaManager $media, ContentWorkflow $workflow)
    {
        $data = $this->itemData($request);
        $newMediaId = null;
        if ($request->hasFile('image')) {
            $newMediaId = $media->store($request->file('image'), $request->user(), [
                'alt_bn' => $data['caption_bn'] ?? '',
                'alt_en' => $data['caption_en'] ?? '',
            ])->id;
            $data['media_file_id'] = $newMediaId;
        }
        $data['gallery_album_id'] = $gallery->id;
        try {
            GalleryItem::create($data);
        } catch (\Throwable $exception) {
            $workflow->cleanUnusedMedia($newMediaId);
            throw $exception;
        }

        return back()->with('status', 'ছবি যোগ হয়েছে।');
    }

    public function updateItem(Request $request, GalleryItem $item, MediaManager $media, ContentWorkflow $workflow)
    {
        $data = $this->itemData($request, $item);
        $oldMediaId = $item->media_file_id;
        $newMediaId = null;
        if ($request->hasFile('image')) {
            $newMediaId = $media->store($request->file('image'), $request->user(), [
                'alt_bn' => $data['caption_bn'] ?? '',
                'alt_en' => $data['caption_en'] ?? '',
            ])->id;
            $data['media_file_id'] = $newMediaId;
        }
        try {
            $item->update($data);
        } catch (\Throwable $exception) {
            $workflow->cleanUnusedMedia($newMediaId);
            throw $exception;
        }
        if ($item->wasChanged('media_file_id')) $workflow->cleanUnusedMedia($oldMediaId);

        return back()->with('status', 'গ্যালারি আইটেম হালনাগাদ হয়েছে।');
    }

    public function destroyItem(GalleryItem $item, ContentWorkflow $workflow)
    {
        $mediaId = $item->media_file_id;
        $item->delete();
        $workflow->cleanUnusedMedia($mediaId);

        return back()->with('status', 'গ্যালারি আইটেম মুছে ফেলা হয়েছে।');
    }

    private function albumData(Request $request, ?GalleryAlbum $album = null): array
    {
        $request->merge(['path' => PublicPath::normalize($request->input('path'))]);
        $data = $request->validate([
            'path' => ['required', 'max:512', new PublicPathAvailable('gallery_albums', $album?->id)],
            'title_bn' => 'required|max:255',
            'title_en' => 'nullable|max:255',
            'description_bn' => 'nullable|max:5000',
            'description_en' => 'nullable|max:5000',
            'sort_order' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);
        $data['path'] = '/'.ltrim($data['path'], '/');

        return $data;
    }

    private function itemData(Request $request, ?GalleryItem $item = null): array
    {
        $data = $request->validate([
            'media_file_id' => ['nullable', Rule::exists('media_files', 'id')->where(fn ($query) => $query->where('mime_type', 'like', 'image/%'))],
            'image' => 'nullable|image|max:10240',
            'caption_bn' => 'nullable|max:255',
            'caption_en' => 'nullable|max:255',
            'link_url' => ['nullable', 'max:2000', new SafeUrl],
            'sort_order' => 'required|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ]);
        if (! $request->hasFile('image') && empty($data['media_file_id']) && ! $item?->url) {
            throw ValidationException::withMessages(['image' => 'একটি ছবি আপলোড বা মিডিয়া লাইব্রেরি থেকে নির্বাচন করুন।']);
        }
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        return $data;
    }
}
