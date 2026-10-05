@extends('layouts.site')
@section('content')<div class="page-heading"><p class="eyebrow">MARI BERKOLABORASI</p><h1>Hubungi Kami</h1></div><div class="contact-grid"><section class="card card-body"><h2>Daygun Research Center</h2><p>{{ $settings['contact_address'] ?? '' }}</p><p>{{ $settings['contact_email'] ?? '' }}</p><p>{{ $settings['contact_phone'] ?? '' }}</p></section>
<form method="post" action="{{ url('kontak/kirim') }}" class="card card-body">@csrf
@foreach(['name'=>'Nama','email'=>'Email','phone'=>'Telepon','subject'=>'Subjek'] as $name=>$label)<label>{{ $label }}<input name="{{ $name }}" type="{{ $name==='email'?'email':'text' }}" value="{{ old($name) }}" @required($name!=='phone') maxlength="{{ $name==='subject'?200:($name==='phone'?20:100) }}"></label>@endforeach
<label>Pesan<textarea name="message" rows="6" required maxlength="10000">{{ old('message') }}</textarea></label><label>Berapa {{ $captcha }}?<input name="captcha" type="number" required></label><button class="button">Kirim Pesan</button></form></div>
@if($map=\App\Support\Html::mapUrl($settings['google_maps_embed']??null))<section class="section"><h2>Lokasi Kami</h2><iframe title="Lokasi Daygun Research Center" src="{{ $map }}" width="100%" height="380" style="border:0" loading="lazy" referrerpolicy="no-referrer" allowfullscreen></iframe></section>@endif
@endsection
