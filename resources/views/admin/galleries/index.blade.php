@extends('layouts.admin')
@section('title', 'গ্যালারি')
@section('content')
<div class="page-head"><h1>ফটো গ্যালারি</h1></div>

<div class="card">
    <h2>গ্যালারি নির্বাচন</h2>
    <div class="actions">
        @forelse($galleries as $gallery)
            <a class="btn {{ $selected?->id === $gallery->id ? '' : 'secondary' }}" href="{{ route('admin.galleries.index', ['gallery' => $gallery->id]) }}">{{ $gallery->title_bn }}</a>
        @empty
            <span class="help">এখনও কোনো গ্যালারি নেই।</span>
        @endforelse
    </div>
    <hr>
    <h3>নতুন গ্যালারি</h3>
    <form method="post" action="{{ route('admin.galleries.store') }}" class="grid">
        @csrf
        <input type="hidden" name="status" value="published">
        <div><label>URL পাথ</label><input name="path" placeholder="/photo-gallery/home" required></div>
        <div><label>বাংলা নাম</label><input name="title_bn" required></div>
        <div><label>English name</label><input name="title_en"></div>
        <div><label>ক্রম</label><input type="number" name="sort_order" value="0" min="0" required></div>
        <div><label>বাংলা বিবরণ</label><textarea name="description_bn"></textarea></div>
        <div><label>English description</label><textarea name="description_en"></textarea></div>
        <div class="full"><button class="btn">নতুন গ্যালারি তৈরি করুন</button></div>
    </form>
</div>

@if($selected)
<div class="card">
    <div class="page-head"><h2>গ্যালারি কনফিগারেশন</h2>
        <form method="post" action="{{ route('admin.galleries.destroy', $selected) }}" data-confirm="গ্যালারি ও এর সব আইটেম ট্র্যাশে পাঠাবেন?">
            @csrf @method('delete')
            <button class="btn danger" type="submit">গ্যালারি মুছুন</button>
        </form>
    </div>
    <form method="post" action="{{ route('admin.galleries.update', $selected) }}" class="grid">
        @csrf @method('put')
        <div><label>URL পাথ</label><input name="path" value="{{ $selected->path }}" required></div>
        <div><label>বাংলা নাম</label><input name="title_bn" value="{{ $selected->title_bn }}" required></div>
        <div><label>English name</label><input name="title_en" value="{{ $selected->title_en }}"></div>
        <div><label>ক্রম</label><input type="number" min="0" name="sort_order" value="{{ $selected->sort_order }}" required></div>
        <div><label>বাংলা বিবরণ</label><textarea name="description_bn">{{ $selected->description_bn }}</textarea></div>
        <div><label>English description</label><textarea name="description_en">{{ $selected->description_en }}</textarea></div>
        <div><label>স্ট্যাটাস</label><select name="status"><option value="published" @selected($selected->status === 'published')>published</option><option value="draft" @selected($selected->status === 'draft')>draft</option></select></div>
        <div style="align-self:end"><button class="btn">সংরক্ষণ</button></div>
    </form>
</div>

<h2>গ্যালারি আইটেম</h2>
@foreach($selectedItems as $item)
<div class="card">
    <form method="post" enctype="multipart/form-data" action="{{ route('admin.gallery-items.update', $item) }}" class="grid">
        @csrf @method('put')
        <div>
            @if($item->url)<img class="thumb" src="{{ $item->url }}" alt="{{ $item->caption_bn }}">@endif
            <label>নতুন ছবি</label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <input type="hidden" name="media_file_id" value="{{ $item->media_file_id }}">
        </div>
        <div><label>বাংলা ক্যাপশন</label><input name="caption_bn" value="{{ $item->caption_bn }}"><label>English caption</label><input name="caption_en" value="{{ $item->caption_en }}"></div>
        <div><label>লিংক</label><input name="link_url" value="{{ $item->link_url }}" placeholder="/pages/... বা https://..."><label>ক্রম</label><input type="number" min="0" name="sort_order" value="{{ $item->sort_order }}" required></div>
        <div><label>শুরু</label><input type="datetime-local" name="starts_at" value="{{ $item->starts_at?->format('Y-m-d\TH:i') }}"><label>শেষ</label><input type="datetime-local" name="ends_at" value="{{ $item->ends_at?->format('Y-m-d\TH:i') }}"><label><input style="width:auto" type="checkbox" name="is_active" value="1" @checked($item->is_active)> সক্রিয়</label></div>
        <div class="full actions"><button class="btn">সংরক্ষণ</button><button class="btn danger" type="submit" form="delete-gallery-item-{{ $item->id }}">মুছুন</button></div>
    </form>
    <form id="delete-gallery-item-{{ $item->id }}" method="post" action="{{ route('admin.gallery-items.destroy', $item) }}" data-confirm="ছবিটি গ্যালারি থেকে সরাবেন?">@csrf @method('delete')</form>
</div>
@endforeach

<div class="card">
    <h2>নতুন ছবি</h2>
    <form method="post" enctype="multipart/form-data" action="{{ route('admin.galleries.items.store', $selected) }}" class="grid">
        @csrf
        <div><label>ছবি আপলোড</label><input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></div>
        <div><label>অথবা মিডিয়া লাইব্রেরি</label><select name="media_file_id"><option value="">—</option>@foreach($media as $medium)<option value="{{ $medium->id }}">{{ $medium->original_name }}</option>@endforeach</select></div>
        <div><label>বাংলা ক্যাপশন</label><input name="caption_bn"><label>English caption</label><input name="caption_en"></div>
        <div><label>লিংক</label><input name="link_url" placeholder="/pages/... বা https://..."><label>ক্রম</label><input type="number" name="sort_order" value="0" min="0" required></div>
        <div><label>শুরু</label><input type="datetime-local" name="starts_at"><label>শেষ</label><input type="datetime-local" name="ends_at"><label><input style="width:auto" type="checkbox" name="is_active" value="1" checked> সক্রিয়</label></div>
        <div style="align-self:end"><button class="btn">যোগ করুন</button></div>
    </form>
</div>
@endif
@endsection
