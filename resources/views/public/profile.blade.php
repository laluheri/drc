@extends('layouts.site')
@section('content')<div class="page-heading"><h1>Profil Lembaga</h1></div><div class="card card-body"><dl>@foreach($settings as $key=>$value)@if(str_starts_with($key,'org_') && $value)<dt>{{ ucwords(str_replace('_',' ',substr($key,4))) }}</dt><dd>{{ $value }}</dd>@endif @endforeach</dl></div>@endsection
