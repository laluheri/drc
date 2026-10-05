@extends('layouts.auth')
@section('content')<form method="post" action="{{ url('admin/forgot-password/process') }}">@csrf<label>Email<input name="email" type="email" value="{{ old('email') }}" required></label><button class="button">Kirim Tautan Reset</button></form><p><a href="{{ route('login') }}">Kembali ke login</a></p>@endsection
