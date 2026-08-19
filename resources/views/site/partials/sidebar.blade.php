<aside class="right"><div class="droppable">
@foreach($sidebar?->items ?? [] as $widget)
    @php $options = $widget->settings ?? []; $type = $options['widget_type'] ?? 'BlockWidget'; $items = $options['items'] ?? []; @endphp
    @if($type === 'InternalEServiceCardWidget')
        <div class="widget e-service-card-widget"><h1 class="e-service-card-header">{{ $widget->localized('label') }}</h1><ul class="e-service-card-body">
            @foreach($items as $item)<li class="e-service-card-list"><div class="e-service-card-image"></div><a class="e-service-card-list-link" href="{{ $localizedUrl($item['href'] ?? '#') }}">{{ $item['label'] ?? '' }}</a></li>@endforeach
        </ul><div class="all-btn"><a href="{{ $localizedUrl($options['all_href'] ?? '#') }}">{{ __('All') }}</a></div></div>
    @elseif($type === 'ImportantLinkCardWidget')
        <div class="widget link-card-widget"><h1 class="link-card-header">{{ $widget->localized('label') }}</h1><ul class="link-card-body">@foreach($items as $item)<li class="link-card-list"><div class="link-card-image"></div><a class="link-card-a" href="{{ $localizedUrl($item['href'] ?? '#') }}">{{ $item['label'] ?? '' }}</a></li>@endforeach</ul></div>
    @elseif($type === 'CentralEServiceLinkWidget')
        <div class="central-service-link-widget widget"><div class="sidebar-link-widget widget"><a class="sidebar-link-widget-link" href="{{ $localizedUrl($widget->url) }}" target="_blank" rel="noopener noreferrer">{{ $widget->localized('label') }}</a></div></div>
    @elseif(filled($widget->localized('text')))
        <div class="widget block-widget"><div class="block-widget-container">@if($widget->localized('label'))<h3 class="block-widget-title">{{ $widget->localized('label') }}</h3>@endif<div class="block-widget-content">{!! $widget->localized('text') !!}</div></div></div>
    @else
        <div class="widget block-widget"><div class="block-widget-container"><h3 class="block-widget-title">{{ $widget->localized('label') }}</h3></div></div>
    @endif
@endforeach
</div></aside>
