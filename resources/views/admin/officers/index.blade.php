@extends('layouts.admin')
@section('title','কর্মকর্তা')
@section('content')
<div class="page-head">
    <h1>{{ request()->boolean('trashed') ? 'ট্র্যাশ: কর্মকর্তাবৃন্দ' : 'কর্মকর্তাবৃন্দ' }}</h1>
    <div class="actions">
        @if(auth()->user()->isPublisher())
            <a class="btn secondary" href="{{ route('admin.officers.index', request()->boolean('trashed') ? [] : ['trashed'=>1]) }}">{{ request()->boolean('trashed') ? 'সক্রিয় তালিকা' : 'ট্র্যাশ' }}</a>
        @endif
        @unless(request()->boolean('trashed'))<a class="btn" href="{{ route('admin.officers.create') }}">নতুন কর্মকর্তা</a>@endunless
    </div>
</div>
<div class="card" style="overflow:auto"><table><thead><tr><th>ক্রম</th><th>ছবি</th><th>নাম/পদবি</th><th>যোগাযোগ</th><th>স্ট্যাটাস</th><th></th></tr></thead><tbody>
@foreach($officers as $officer)<tr><td>{{ $officer->sort_order }}</td><td>@if($officer->photo)<img class="thumb" src="{{ $officer->photo }}" alt="">@endif</td><td><strong>{{ $officer->name_bn }}</strong><br>{{ $officer->designation_bn }}</td><td>{{ $officer->email }}<br>{{ $officer->mobile }}</td><td><span class="badge {{ $officer->status }}">{{ $officer->status }}</span></td><td><div class="actions">
@if($officer->trashed())
    <form method="post" action="{{ route('admin.officers.restore',$officer->id) }}">@csrf @method('patch')<button class="btn small">পুনরুদ্ধার</button></form>
    <form method="post" action="{{ route('admin.officers.force-destroy',$officer->id) }}" data-confirm="কর্মকর্তা ও অব্যবহৃত ছবি স্থায়ীভাবে মুছবেন?">@csrf @method('delete')<button class="btn small danger">স্থায়ীভাবে মুছুন</button></form>
@else
    <a class="btn small secondary" href="{{ route('admin.officers.edit',$officer) }}">সম্পাদনা</a>
    @if(in_array($officer->status,['draft','rejected']))<form method="post" action="{{ route('admin.officers.submit',$officer) }}">@csrf<button class="btn small">জমা</button></form>@endif
    @if(auth()->user()->isPublisher()&&in_array($officer->status,['draft','pending_review','rejected']))<form method="post" action="{{ route('admin.officers.publish',$officer) }}">@csrf<button class="btn small">প্রকাশ</button></form>@endif
    @if(auth()->user()->isPublisher())<form method="post" action="{{ route('admin.officers.destroy',$officer) }}" data-confirm="ট্র্যাশে পাঠাবেন?">@csrf @method('delete')<button class="btn small danger">মুছুন</button></form>@endif
@endif
</div></td></tr>@endforeach
</tbody></table>{{ $officers->links() }}</div>
@endsection
