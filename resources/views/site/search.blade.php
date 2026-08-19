@extends('layouts.site')
@section('title', __('Search'))
@section('content')
<div class="widget datatable-widget"><div class="title-bar"><h2>{{ __('Search results') }}</h2></div><form class="container-group" method="get"><div class="searchbar"><input name="q" value="{{ $term }}" minlength="2" placeholder="{{ __('Search here...') }}"><button type="submit"><i class="ph ph-magnifying-glass"></i></button></div></form>
@if(mb_strlen($term) < 2)<div class="empty-state">{{ __('Enter at least two characters.') }}</div>@else<ul class="search-results">@forelse($results as $result)<li><a href="{{ $localizedUrl($result->path) }}"><strong>{{ $result->localized('title') }}</strong></a><p>{{ \Illuminate\Support\Str::limit(strip_tags($result->localized('body')), 180) }}</p></li>@empty<li class="empty-state">{{ __('No results found.') }}</li>@endforelse</ul>@endif
</div>
@endsection
