<article class="card">
@php($image = $item->featured_image ?? $item->cover_image ?? $item->photo ?? $item->image ?? $item->logo)
@if($image)<img class="card-image" src="{{ \App\Support\Media::url($image) }}" alt="{{ $item->label() }}" loading="lazy">@endif
<div class="card-body"><h3>@if(in_array($path,['berita','jurnal','buku','galeri']) && $item->slug)<a href="{{ url($path.'/'.$item->slug) }}">{{ $item->label() }}</a>@else{{ $item->label() }}@endif</h3>
@if($item->position)<p class="eyebrow">{{ $item->position }}</p>@endif
@if($item->authors)<p>{{ $item->authors }} {{ $item->published_year ? '· '.$item->published_year : '' }}</p>@endif
@if($item->event_date)<p class="eyebrow">{{ $item->event_date }} {{ $item->event_time }} · {{ $item->location }}</p>@endif
@if($path!=='faq')<p>{{ \Illuminate\Support\Str::limit(strip_tags($item->excerpt ?? $item->description ?? $item->abstract ?? $item->synopsis ?? $item->biography ?? ''),200) }}</p>@endif
@if($path==='tim')<p>{{ $item->education }} {{ $item->profession ? '· '.$item->profession : '' }}</p><div class="actions">@foreach(['facebook','twitter','linkedin','instagram'] as $platform)@if(\App\Support\Html::safeUrl($item->$platform))<a href="{{ $item->$platform }}" rel="noopener" target="_blank">{{ ucfirst($platform) }}</a>@endif @endforeach</div>@endif
@if($path==='faq')<div class="prose">{!! \App\Support\Html::clean($item->answer) !!}</div>@endif
@if(in_array($path,['dokumen','download']) && $item->file_path)<a class="button small" href="{{ route('download',['module'=>$path==='dokumen'?'documents':'downloads','id'=>$item->id]) }}">Unduh file</a>@endif
@if($item->registration_url && \App\Support\Html::safeUrl($item->registration_url))<a class="button small" href="{{ $item->registration_url }}">Daftar kegiatan</a>@endif
@if($item->website_url && \App\Support\Html::safeUrl($item->website_url))<a href="{{ $item->website_url }}" target="_blank" rel="noopener">Kunjungi website →</a>@endif
</div></article>
