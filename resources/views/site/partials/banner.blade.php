<div class="widget banner-slider-image-widget">
    <div class="home-carousel"><div class="slider images">
        <a href="{{ $mediaUrl($setting('site.hero_image')) }}" target="_blank" rel="noopener noreferrer"><img class="slider-image" src="{{ $mediaUrl($setting('site.hero_image')) }}" alt="{{ $setting('site.hero_alt') }}"></a>
        <div class="slider-overlay widget-container-row">
            <div class="slider-left container-col-4">
                <a href="{{ app()->getLocale() === 'en' ? '/en' : '/' }}"><img class="office-logo" src="{{ $mediaUrl($setting('site.logo', '/assets/logo.png')) }}" alt="{{ __('Office logo') }}"></a>
                <div class="office-left-section"><h1><a style="text-decoration:none" href="{{ app()->getLocale() === 'en' ? '/en' : '/' }}" class="office-title">{{ $setting('site.office_title') }}</a></h1><p class="office-subtitle">{{ $setting('site.office_subtitle') }}</p></div>
            </div>
            <div class="slider-controls container-col-4">
                <button class="nav-btn slider-previous" type="button" aria-label="{{ __('Previous') }}">❮</button>
                <button class="nav-btn slider-play" type="button" aria-label="{{ __('Pause') }}"><i class="ph-fill ph-play-circle"></i></button>
                <button class="nav-btn slider-next" type="button" aria-label="{{ __('Next') }}">❯</button>
            </div>
        </div>
    </div></div>
</div>
