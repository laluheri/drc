@extends('layouts.admin')
@section('content')<article class="card card-body"><p><strong>{{ $item->name }}</strong> · {{ $item->email }} · {{ $item->phone }}</p><p>{{ $item->created_at }}</p><div class="message">{{ $item->message }}</div></article><a href="{{ url('admin/messages') }}">← Pesan Masuk</a>@endsection
