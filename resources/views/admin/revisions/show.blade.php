@extends('layouts.admin')
@section('title', 'সংশোধন পর্যালোচনা')
@section('content')
<div class="page-head"><h1>সংশোধন পর্যালোচনা</h1><a class="btn secondary" href="{{ route('admin.revisions.index') }}">ফিরুন</a></div>
<div class="card">
    <p><strong>সম্পাদক:</strong> {{ $revision->user?->name }} · <strong>স্ট্যাটাস:</strong> <span class="badge {{ $revision->action === 'submitted' ? 'pending_review' : $revision->action }}">{{ $revision->action }}</span></p>
    @if($revision->note)<p><strong>নোট:</strong> {{ $revision->note }}</p>@endif
</div>
<div class="grid">
    <div class="card"><h2>বর্তমান প্রকাশিত মান</h2><dl>
        @foreach(($revision->revisionable?->getAttributes() ?? []) as $key => $value)
            <dt><strong>{{ $key }}</strong></dt><dd style="white-space:pre-wrap;word-break:break-word">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : $value }}</dd>
        @endforeach
    </dl></div>
    <div class="card"><h2>প্রস্তাবিত মান</h2><dl>
        @foreach(($revision->snapshot ?? []) as $key => $value)
            @continue(str_starts_with($key, '_'))
            @if($revision->revisionable?->getAttribute($key) != $value)
                <dt><strong>{{ $key }} <span class="badge pending_review">পরিবর্তিত</span></strong></dt>
                <dd style="white-space:pre-wrap;word-break:break-word">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : $value }}</dd>
            @endif
        @endforeach
    </dl></div>
</div>

@if($revision->is_working && $revision->action === 'submitted' && auth()->user()->isPublisher())
<div class="card">
    <form method="post" action="{{ route('admin.revisions.approve', $revision) }}">@csrf<label>পর্যালোচনা নোট (ঐচ্ছিক)</label><textarea name="note"></textarea><button class="btn">অনুমোদন ও প্রকাশ</button></form>
    <hr>
    <form method="post" action="{{ route('admin.revisions.reject', $revision) }}">@csrf<label>ফেরত দেওয়ার কারণ</label><textarea name="note" required></textarea><button class="btn danger">সংশোধনের জন্য ফেরত দিন</button></form>
</div>
@elseif($revision->is_working && in_array($revision->action, ['draft','rejected']) && ($revision->user_id === auth()->id() || auth()->user()->isPublisher()))
<div class="card actions">
    @if($revision->action === 'draft')
        <form method="post" action="{{ route('admin.revisions.submit', $revision) }}">@csrf<button class="btn">অনুমোদনের জন্য জমা দিন</button></form>
    @else
        @php $editRoute = match($revision->revisionable_type) { \App\Models\Page::class => 'admin.pages.edit', \App\Models\ContentItem::class => 'admin.content.edit', \App\Models\Officer::class => 'admin.officers.edit', default => null }; @endphp
        @if($editRoute && $revision->user_id === auth()->id())<a class="btn" href="{{ route($editRoute, $revision->revisionable_id) }}">প্রকাশিত কনটেন্ট থেকে নতুন সংশোধন তৈরি করুন</a>@endif
    @endif
    <form method="post" action="{{ route('admin.revisions.destroy', $revision) }}" data-confirm="খসড়া ও শুধু এটির জন্য আপলোড করা মিডিয়া মুছবেন?">@csrf @method('delete')<button class="btn danger">খসড়া বাতিল করুন</button></form>
</div>
@endif
@endsection
