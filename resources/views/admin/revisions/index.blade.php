@extends('layouts.admin')
@section('title', auth()->user()->isPublisher() ? 'অনুমোদন' : 'আমার সংশোধন')
@section('content')
<div class="page-head"><h1>{{ auth()->user()->isPublisher() ? 'সংশোধন অনুমোদন' : 'আমার সংশোধন' }}</h1></div>
<form class="card" method="get">
    <label>স্ট্যাটাস</label>
    <select name="action" onchange="this.form.submit()">
        <option value="">{{ auth()->user()->isPublisher() ? 'অপেক্ষমাণ' : 'সক্রিয় সংশোধন' }}</option>
        @foreach(['draft','submitted','published','rejected','superseded'] as $action)
            <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
        @endforeach
    </select>
</form>
<div class="card" style="overflow:auto">
<table>
    <thead><tr><th>কনটেন্ট</th><th>সম্পাদক</th><th>সময়</th><th>স্ট্যাটাস</th><th></th></tr></thead>
    <tbody>
    @forelse($revisions as $revision)
        <tr>
            <td>{{ class_basename($revision->revisionable_type) }} #{{ $revision->revisionable_id }}<br><strong>{{ data_get($revision->snapshot, 'title_bn') ?? data_get($revision->snapshot, 'name_bn') }}</strong></td>
            <td>{{ $revision->user?->name }}</td>
            <td>{{ $revision->created_at?->format('d-m-Y H:i') }}</td>
            <td><span class="badge {{ $revision->action === 'submitted' ? 'pending_review' : $revision->action }}">{{ $revision->action }}</span></td>
            <td><div class="actions">
                <a class="btn small secondary" href="{{ route('admin.revisions.show', $revision) }}">পর্যালোচনা</a>
                @if($revision->is_working && in_array($revision->action, ['draft','rejected']) && ($revision->user_id === auth()->id() || auth()->user()->isPublisher()))
                    @if($revision->action === 'draft')
                        <form method="post" action="{{ route('admin.revisions.submit', $revision) }}">@csrf<button class="btn small">জমা দিন</button></form>
                    @else
                        @php $editRoute = match($revision->revisionable_type) { \App\Models\Page::class => 'admin.pages.edit', \App\Models\ContentItem::class => 'admin.content.edit', \App\Models\Officer::class => 'admin.officers.edit', default => null }; @endphp
                        @if($editRoute && $revision->user_id === auth()->id())<a class="btn small" href="{{ route($editRoute, $revision->revisionable_id) }}">নতুন করে সংশোধন করুন</a>@endif
                    @endif
                    <form method="post" action="{{ route('admin.revisions.destroy', $revision) }}" data-confirm="খসড়া ও শুধু এটির জন্য আপলোড করা মিডিয়া মুছবেন?">@csrf @method('delete')<button class="btn small danger">বাতিল করুন</button></form>
                @endif
            </div></td>
        </tr>
    @empty
        <tr><td colspan="5">কোনো সংশোধন নেই।</td></tr>
    @endforelse
    </tbody>
</table>
{{ $revisions->links() }}
</div>
@endsection
