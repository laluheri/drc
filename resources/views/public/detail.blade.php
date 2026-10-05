@extends('layouts.site')
@section('content')<article class="detail"><p class="eyebrow">DAYGUN RESEARCH CENTER</p><h1>{{ $item->label() }}</h1>
@php($image=$item->featured_image ?? $item->cover_image)
@if($image)<img class="detail-image" src="{{ \App\Support\Media::url($image) }}" alt="{{ $item->label() }}">@endif
@if($item->authors)<p class="muted">{{ $item->authors }} · {{ $item->published_year }} {{ $item->isbn ? '· ISBN '.$item->isbn : '' }}</p>@endif
<div class="prose">{!! \App\Support\Html::clean($item->content ?? $item->abstract ?? $item->synopsis ?? $item->description) !!}</div>
@if(in_array($table,['books','journals']) && $item->pdf_file)<a class="button" href="{{ route('download',['module'=>$table,'id'=>$item->id]) }}">Unduh PDF</a>@endif
<div class="grid">@foreach($images as $media)<article class="card">@if($media->image_path)<img class="card-image" src="{{ \App\Support\Media::url($media->image_path) }}" alt="{{ $media->title }}">@endif<div class="card-body"><h3>{{ $media->title }}</h3>@if(\App\Support\Html::safeUrl($media->youtube_url))<a href="{{ $media->youtube_url }}" target="_blank" rel="noopener">Tonton video →</a>@endif</div></article>@endforeach</div>
</article>@if($related->isNotEmpty())<section class="section"><h2>Berita Terkait</h2><div class="grid">@foreach($related as $item)@include('partials.card')@endforeach</div></section>@endif @endsection
