<section class="widget menus-expandable-widget max-view"><div class="menus-widget-container" style="--home-label:'{{ __('Home') }}'"><section class="widget menu-widget">
    <button id="menu-toggle" class="hamburger-menu-block" type="button"><i class="hamburger-menu ph ph-list"></i><span>{{ __('Select menu') }}</span></button>
    <ul class="menu-list menu-parent-unordered-list custom-items-center">
        @foreach($primaryMenu?->items ?? [] as $top)
            @php $children = $top->children; $hasChildren = $children->isNotEmpty(); $topUrl = $localizedUrl($top->resolvedUrl()); @endphp
            <li class="megamenu-link {{ $hasChildren ? 'menu-parent-list' : '' }}">
                @if($hasChildren)
                    <a title="{{ $top->localized('label') }}" href="#" class="menu-parent-list-link" data-menu-toggle>{{ $top->localized('label') }}<i class="menu-parent-list-link-icon ph ph-caret-double-down"></i></a>
                @else
                    <a href="{{ $topUrl }}" target="{{ $top->target }}" @if($top->target === '_blank') rel="noopener noreferrer" @endif title="{{ $top->localized('label') }}" class="menu-parent-list-link {{ $topUrl === '/' || $topUrl === '/en' ? 'home-link' : '' }}">{{ $topUrl === '/' || $topUrl === '/en' ? '' : $top->localized('label') }}</a>
                @endif
                @if($hasChildren)
                    <div class="mega-menu-dropdown megaMenu">
                        @php $direct = $children->filter(fn($child) => $child->children->isEmpty()); @endphp
                        @if($direct->isNotEmpty())
                            <div class="menu-child-box"><h6 class="menu-child-title"></h6><ul class="menu-sub-child-unordered-list">
                                @foreach($direct as $child)<li class="menu-sub-child-list"><a title="{{ $child->localized('label') }}" class="menu-sub-child-link" href="{{ $localizedUrl($child->resolvedUrl()) }}" target="{{ $child->target }}" @if($child->target === '_blank') rel="noopener noreferrer" @endif><div>{{ $child->localized('label') }}</div></a></li>@endforeach
                            </ul></div>
                        @endif
                        @foreach($children->filter(fn($child) => $child->children->isNotEmpty()) as $group)
                            <div class="menu-child-box"><h6 title="{{ $group->localized('label') }}" class="menu-child-title"><span>{{ $group->localized('label') }}</span></h6><ul class="menu-sub-child-unordered-list">
                                @foreach($group->children as $child)<li class="menu-sub-child-list"><a title="{{ $child->localized('label') }}" class="menu-sub-child-link" href="{{ $localizedUrl($child->resolvedUrl()) }}" target="{{ $child->target }}" @if($child->target === '_blank') rel="noopener noreferrer" @endif><div>{{ $child->localized('label') }}</div></a></li>@endforeach
                            </ul></div>
                        @endforeach
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</section></div></section>
