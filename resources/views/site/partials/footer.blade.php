<div class="footer"><div class="droppable"><footer class="widget footer-widget">
    <div class="footer-widget-image"></div><div class="footer-disclaimer">{{ $footerText }}</div>
    <div class="footer-body"><div><ul class="left-ul">@foreach($footerLinks as $link)<li class="left-ul-list"><a class="footer-link" href="{{ $localizedUrl($link['href'] ?? '#') }}">{{ $link['label'] ?? '' }}</a></li>@endforeach</ul><div class="site-update-block"><p>{{ __('Site last updated') }}: {{ $setting('site.last_updated', now()->translatedFormat('l, j F Y')) }}</p></div></div>
        <div class="text-xs"><p>{{ $footerCredit }}</p><div class="technical-support-block"><p class="technical-support-text">{{ $footerSupportLabel }}</p><img alt="{{ $footerSupportLabel }}" class="technical-support-image" src="{{ $mediaUrl($footerSupportImage) }}"></div></div>
    </div>
</footer></div></div>
