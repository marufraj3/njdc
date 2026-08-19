@extends('layouts.site')
@section('title', __('Home'))
@section('content')
<section class="widget notice-news-card-widget">
    <div class="notice-card"><p class="notice-title"><i class="ph ph-file-text"></i> {{ __('Notice board') }}</p><ul class="notice-unordered-list">
        @forelse($notices as $notice)
            <li class="notice-content-list"><a class="notice-link" href="{{ $localizedUrl($notice->path) }}"><div class="notice-content-icon"><i class="dot"></i></div><div class="notice-text-wrap"><p class="notice-text" title="{{ $notice->localized('title') }}">{{ $notice->localized('title') }}</p><p class="notice-text"><span class="notice-tag"><i class="ph ph-calendar-dots"></i> {{ $notice->metadata['display_date'] ?? optional($notice->published_at)->format('d-m-Y') }}</span>@if($notice->metadata['tag'] ?? null)<strong class="notice-tag">{{ $notice->metadata['tag'] }}</strong>@endif</p></div><div class="notice-content-icon"><i class="ph ph-caret-right"></i></div></a></li>
        @empty<li class="empty-state">{{ __('No notices found.') }}</li>@endforelse
    </ul></div>
    <div class="all-btn"><a href="{{ $localizedUrl('/pages/notices') }}">{{ __('View all notices') }} <i class="ph ph-arrow-right"></i></a></div>
    <div class="news-card"><section class="widget news-card-widget"><div class="news-card-widget-scroll-container"><div class="news-card-widget-news-title">{{ __('News') }}</div><div class="news-card-widget-ticker">
        @foreach($news as $item)<a href="{{ $localizedUrl($item['href'] ?? '#') }}" class="new-content scroll-text ticker-item">{{ $item['title'] ?? $item['label'] ?? '' }}</a>@endforeach
    </div><div class="all-btn"><a href="{{ $localizedUrl('/pages/news') }}">{{ __('All') }}</a></div></div></section></div>
</section>

@if($gallery?->items?->isNotEmpty())
<section class="widget home-photo-slider-widget"><div class="home-photo-slider-widget-carousel">
    @foreach($gallery->items as $photo)<div class="home-photo-slider-widget-slider home-photo-slider-widget-images photo-slide {{ $loop->first ? 'active' : '' }}" style="background-image:url('{{ $mediaUrl($photo->url) }}')">@if($photo->link_url)<a href="{{ $localizedUrl($photo->link_url) }}">@endif<img class="home-photo-slider-widget-slider-image" src="{{ $mediaUrl($photo->url) }}" alt="{{ $photo->localized('caption') }}"><div class="photo-slider-caption">{{ $photo->localized('caption') }}</div>@if($photo->link_url)</a>@endif</div>@endforeach
    <button class="home-photo-slider-widget-slider-previous" type="button" data-photo-prev>❮</button><button class="home-photo-slider-widget-slider-next" type="button" data-photo-next>❯</button>
</div><br><div class="home-photo-slider-widget-block"><div class="home-photo-slider-widget-navigator">@foreach($gallery->items as $photo)<img class="home-photo-slider-widget-slider-navigation-img {{ $loop->first ? 'active' : '' }}" src="{{ $mediaUrl($photo->url) }}" alt="{{ $photo->localized('caption') }}" data-photo-index="{{ $loop->index }}">@endforeach</div></div></section>
@endif

@php $services = $homeSections->get('service-boxes'); @endphp
@if($services)
<section class="widget service-box-expandable-stack-widget"><section class="widget service-box-stack-widget widget-container-row"><div class="service-box-stack-widget-header"><p class="service-box-stack-widget-title">{{ $services->localized('title') }}</p><a class="service-box-stack-widget-link" href="{{ $localizedUrl($services->settings['all_href'] ?? '#') }}">{{ __('View all') }}</a></div>
    @foreach($services->items as $box)<div class="container-col-6 {{ $loop->index >= 8 ? 'service-box-extra' : '' }}"><div class="widget service-box-widget"><h1 class="service-box-title" style="color:black">{{ $box->localized('label') }}</h1><div class="service-box-grid"><div class="service-box-col-span-4 service-box-img-container">@if($box->media_url)<img alt="{{ $box->localized('label') }}" src="{{ $mediaUrl($box->media_url) }}">@endif</div><div class="service-box-col-span-8"><ul class="service-box-list">@foreach($box->children as $link)<li class="service-box-list-item"><div class="service-box-bullet"></div><a class="service-box-list-link" href="{{ $localizedUrl($link->url) }}" title="{{ $link->localized('label') }}">{{ $link->localized('label') }}</a></li>@endforeach</ul></div></div></div></div>@endforeach
</section>@if($services->items->count() > 8)<div class="all-btn-wrapper"><button class="all-btn" type="button" data-expand-services><span class="expand-label">{{ __('View all services') }}</span> <i class="ph ph-caret-down"></i></button></div>@endif</section>
@endif

@foreach($homeSections->get('content-blocks')?->items ?? [] as $block)<div class="widget block-widget"><div class="block-widget-container">@if($block->localized('label'))<h3 class="block-widget-title">{{ $block->localized('label') }}</h3>@endif<div class="block-widget-content">{!! $block->localized('text') !!}</div></div></div>@endforeach

@php $contact = $homeSections->get('contact'); @endphp
@if($contact)
<div class="get-in-touch-widget widget"><div class="get-in-touch-container"><div class="get-in-touch-row"><div class="get-in-touch-col content-col"><h2 class="get-in-touch-title">{{ $contact->localized('title') }}</h2><div class="office-info"><h3 class="office-name">{{ $contact->settings['office'] ?? '' }}</h3><ul class="contact-list">@foreach($contact->items as $item)<li><i class="{{ $item->icon }}"></i><b class="label">{{ $item->localized('label') }} </b><span>{{ $item->localized('text') }}</span></li>@endforeach</ul></div><div class="social-media-container">@foreach($contact->settings['social_links'] ?? [] as $social)<div class="widget social-link-media-widget"><a href="{{ $localizedUrl($social['url'] ?? '#') }}" title="{{ $social['label'] ?? '' }}" target="_blank" rel="noopener noreferrer"><i class="{{ $social['icon'] ?? '' }} media-icon social-link-media-widget-facebook-icon" style="color:{{ $social['color'] ?? '#333' }}"></i></a></div>@endforeach</div></div><div class="get-in-touch-col map-col"><div class="map-container"><div class="office-location-widget widget"><div class="office-location-widget-container"><h2 class="office-location-widget-title">{{ $contact->settings['map_title'] ?? '' }}</h2><div class="office-location-widget-iframe-container"><iframe class="office-location-widget-iframe" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="{{ $mediaUrl($contact->settings['map_src'] ?? '') }}" title="{{ __('Map') }}"></iframe></div></div></div></div></div></div></div></div>
@endif
@endsection
