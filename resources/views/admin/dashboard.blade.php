@extends('layouts.admin')
@section('title','ড্যাশবোর্ড')
@section('content')
<div class="page-head"><h1>কনটেন্ট ড্যাশবোর্ড</h1></div><div class="stats">@foreach($counts as $label=>$count)<div class="card stat"><span>{{ ucfirst($label) }}</span><strong>{{ $count }}</strong></div>@endforeach</div><div class="card"><h2>অনুমোদনের অপেক্ষায়</h2><p><strong>{{ $pending }}</strong>টি কনটেন্ট এবং <strong>{{ $revisions->count() }}</strong>টি সংশোধন পর্যালোচনার জন্য অপেক্ষা করছে।</p>@if(auth()->user()->isPublisher() && $revisions->isNotEmpty())<table><thead><tr><th>ধরন</th><th>আইডি</th><th>জমাদানকারী</th><th>তারিখ</th></tr></thead><tbody>@foreach($revisions as $revision)<tr><td>{{ class_basename($revision->revisionable_type) }}</td><td>{{ $revision->revisionable_id }}</td><td>{{ $revision->user_id }}</td><td>{{ $revision->created_at }}</td></tr>@endforeach</tbody></table>@endif</div>
@endsection
