@extends('layouts.site')
@section('title', $record->localized($record instanceof \App\Models\Officer ? 'name' : 'title'))
@section('content')
@include('site.partials.share')

@if($record instanceof \App\Models\Officer)
<div class="widget content-viewer-widget"><div class="widget print-widget"><div></div><button type="button" data-print title="{{ __('Print') }}"><i class="ph ph-printer"></i></button></div><div class="widget officer-details-widget"><h1 class="content-title-content">{{ __('Officers') }}</h1><div class="list-card-body"><div class="image-section">@if($record->photo)<img alt="{{ $record->localized('name') }}" class="list-card-image" src="{{ $mediaUrl($record->photo) }}">@endif</div><div class="right-section"><table><tbody>
@foreach([[__('Name'),$record->localized('name')],[__('Designation'),$record->localized('designation')],[__('Office'),$record->localized('office')],[__('Email'),$record->email],[__('Office phone'),$record->office_phone],[__('Intercom'),$record->intercom],[__('Room number'),$record->room],[__('Mobile'),$record->mobile],[__('Fax'),$record->fax]] as [$label,$value])<tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>@endforeach
</tbody></table></div></div></div></div>

@elseif($record instanceof \App\Models\GalleryAlbum)
<div class="widget content-viewer-widget"><div class="widget print-widget"><div></div><button type="button" data-print title="{{ __('Print') }}"><i class="ph ph-printer"></i></button></div><div class="widget title-with-image-content-widget"><h2>{{ $record->localized('title') }}</h2>@if($record->localized('description'))<p>{{ $record->localized('description') }}</p>@endif<div class="image-list gallery-album-grid">@forelse($record->items as $photo)<a href="{{ $photo->link_url ? $localizedUrl($photo->link_url) : $mediaUrl($photo->url) }}" @unless($photo->link_url) target="_blank" rel="noopener noreferrer" @endunless><img src="{{ $mediaUrl($photo->url) }}" alt="{{ $photo->localized('caption') }}">@if($photo->localized('caption'))<span>{{ $photo->localized('caption') }}</span>@endif</a>@empty<p>{{ __('No content found.') }}</p>@endforelse</div></div></div>

@elseif($record instanceof \App\Models\Page && $record->template === 'officers')
<div class="widget content-browse-widget"><div class="title-bar"><h2>{{ $record->localized('title') }}</h2></div><div class="container-group"><div class="searchbar"><input placeholder="{{ __('Search') }}" type="text" data-table-search><button type="button"><i class="ph ph-magnifying-glass"></i></button></div></div><div class="browse-items view-type-list grouped-view"><h3>{{ __('Officers') }}</h3>
@foreach($officers as $officer)<div class="widget employee-content-widget"><div class="list-card-body"><div class="list-card-body-sl">{{ $loop->iteration }}</div><div class="image-section">@if($officer->photo)<img alt="{{ $officer->localized('name') }}" class="list-card-image" src="{{ $mediaUrl($officer->photo) }}">@endif</div><div class="right-section"><table><tbody><tr><td>{{ __('Name') }}</td><td>{{ $officer->localized('name') }}</td></tr><tr><td>{{ __('Designation') }}</td><td>{{ $officer->localized('designation') }}</td></tr><tr><td>{{ __('Office') }}</td><td>{{ $officer->localized('office') }}</td></tr><tr><td>{{ __('Email') }}</td><td>{{ $officer->email }}</td></tr></tbody></table></div><div class="right-section"><table><tbody><tr><td>{{ __('Office phone') }}</td><td>{{ $officer->office_phone }}</td></tr><tr><td>{{ __('Intercom') }}</td><td>{{ $officer->intercom }}</td></tr><tr><td>{{ __('Room number') }}</td><td>{{ $officer->room }}</td></tr><tr><td>{{ __('Mobile') }}</td><td>{{ $officer->mobile }}</td></tr><tr><td>{{ __('Fax') }}</td><td>{{ $officer->fax }}</td></tr></tbody></table><div class="see-all-btn-block"><a class="see-all-btn" href="{{ $localizedUrl($officer->path) }}">{{ __('View') }}</a></div></div></div></div>@endforeach
</div></div>

@elseif($record instanceof \App\Models\Page && $record->template === 'table')
<div class="widget datatable-widget"><div class="title-bar"><h2>{{ $record->localized('title') }}</h2></div><div class="container-group"><div class="searchbar"><input placeholder="{{ __('Search') }}" type="text" data-table-search><button type="button"><i class="ph ph-magnifying-glass"></i></button></div><div class="pagesize-dropdown"><label>{{ __('Page items') }} </label><select><option>10</option><option>20</option><option>50</option><option>100</option></select></div></div>
<div style="overflow:auto"><table class="notice-table" data-filter-table><thead class="table-thead"><tr class="table-tr">@foreach($record->metadata['columns'] ?? [] as $column)<th class="table-th">{{ $column }}</th>@endforeach</tr></thead><tbody class="table-tbody">@foreach($record->metadata['rows'] ?? [] as $row)<tr class="table-tr">@foreach($row as $cell)<td class="table-td">@if($cell['img'] ?? null)<a class="table-td-icon" href="{{ $mediaUrl($cell['img']) }}" target="_blank" rel="noopener noreferrer"><img src="{{ $mediaUrl($cell['img']) }}" alt=""></a>@elseif($cell['href'] ?? null)<a href="{{ $localizedUrl($cell['href']) }}">{{ $cell['text'] ?? '' }}</a>@else{{ $cell['text'] ?? '' }}@endif</td>@endforeach</tr>@endforeach</tbody></table></div><div class="pagination-meta"><span class="pagination-counts">{{ __('Total entries') }}: {{ count($record->metadata['rows'] ?? []) }}</span></div></div>

@elseif($record instanceof \App\Models\Page && $record->template === 'raw')
{!! $record->localized('body') !!}

@else
@php
    $attachments = $record->attachments ?? collect();
    $files = $attachments->where('role', 'attachment')->values();
    $images = $attachments->where('role', 'image')->values();
    $metadata = $record->metadata ?? [];
@endphp
<div class="widget content-viewer-widget"><div class="content-update-block"><p>{{ $metadata['legacy_updated'] ?? optional($record->updated_at)->translatedFormat('l, j F Y') }}</p></div><div class="widget print-widget"><div></div><button type="button" data-print title="{{ __('Print') }}"><i class="ph ph-printer"></i></button></div><div class="widget title-with-image-content-widget"><h2>{{ $record->localized('title') }}</h2>
@if($metadata['chips'] ?? [])<p class="chips">@foreach($metadata['chips'] as $chip)<span class="basic-chip">{{ $chip }}</span>@endforeach</p>@endif
@if($images->isNotEmpty())<div class="image-list" style="display:block">@foreach($images as $image)<img src="{{ $mediaUrl($image->media->url) }}" alt="{{ $image->localized('label') ?: $image->media->localized('alt') }}">@endforeach</div>@endif
<div class="content-body">{!! $record->localized('body') !!}</div>
@if($files->isNotEmpty())<div class="title-with-image-content-widget-content-files active"><div class="chip-list">@foreach($files as $file)<button class="chip {{ $loop->first ? 'active' : '' }}" type="button" data-file-tab="{{ $loop->index }}">{{ $file->localized('label') ?: $file->media->original_name }} <a href="{{ $mediaUrl($file->media->url) }}" target="_blank" rel="noopener" title="{{ __('Download') }}"><i class="ph ph-download-simple download-icon"></i></a></button>@endforeach</div>@foreach($files as $file)<div class="title-with-image-content-widget-content-pdf" data-file-preview="{{ $loop->index }}" @if(!$loop->first) hidden @endif><object data="{{ $mediaUrl($file->media->url) }}" height="600" type="application/pdf" width="100%"><h3>{{ __('File preview is not supported in this browser') }}</h3><a href="{{ $mediaUrl($file->media->url) }}" target="_blank" rel="noopener">{{ __('Download') }}</a></object></div>@endforeach</div>@endif
</div></div>
@endif

@include('site.partials.share')
@endsection
