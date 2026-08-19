<section class="widget header-widget-section">
    <div class="header-left-section">
        <a class="header-title" href="{{ $localizedUrl($setting('site.portal_url', 'https://bangladesh.gov.bd')) }}" title="{{ $setting('site.portal_title') }}">{{ $setting('site.portal_title') }}</a>
    </div>
    <div class="header-left-section">
        <div class="widget header-dropdown custom-items-center top-menu office-findthree-widget office-findv2-widget">
            <div class="office-group">
                <select title="{{ __('Directorate') }}">
                    @foreach([__('Select office type'), __('Autonomous'), __('Ministry'), __('Ministry division'), __('Directorate'), __('Corporation'), __('Commission'), __('Company'), __('Authority'), __('Agency'), __('Bank / insurance / financial institution'), __('Divisional portal'), __('District portal'), __('Municipality portal'), __('Upazila portal'), __('Union portal'), __('Project'), __('Training')] as $type)
                        <option @selected($loop->iteration === 5)>{{ $type }}</option>
                    @endforeach
                </select>
                <div class="dynamic-dropdowns"></div><button disabled>{{ __('View') }}</button>
            </div>
        </div>
    </div>
    <div class="global-searchbar custom-items-center">
        <form class="widget global-search-widget" method="get" action="{{ app()->getLocale() === 'en' ? '/en/search' : '/search' }}">
            <input class="input-search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search here...') }}" aria-label="{{ __('Search') }}">
            <button class="btn-search" type="submit">{{ __('Search') }}</button>
        </form>
        <div class="widget language-switcher-widget">
            @php $languageUrl = app()->getLocale() === 'en' ? preg_replace('#^/en(?:/|$)#', '/', request()->getPathInfo()) : '/en'.(request()->getPathInfo() === '/' ? '' : request()->getPathInfo()); @endphp
            <a class="btn-lang-change" href="{{ $languageUrl }}">{{ app()->getLocale() === 'en' ? 'বাংলা' : 'English' }}</a>
        </div>
        @auth<a class="admin-link" href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a>@endauth
    </div>
</section>
