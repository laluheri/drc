@extends('layouts.admin')
@section('content')<form method="post" enctype="multipart/form-data" action="{{ url('admin/'.$module.($item->exists?'/update/'.$item->id:'/store')) }}" class="card card-body form-grid">@csrf
@foreach($fields as $name=>$field)@php($value=old($name,$item->$name ?? ($field['default'] ?? '')))
<label>{{ ucwords(str_replace('_',' ',$name)) }}{{ $field['required']?' *':'' }}
@if(in_array($name,array_merge(config('drc.images'),config('drc.files'))))<input type="file" name="{{ $name }}" accept="{{ in_array($name,config('drc.images'))?'image/jpeg,image/png,image/gif,image/webp':'.pdf,.doc,.docx' }}">@if($item->$name)<small>File saat ini: {{ $item->$name }}. Kosongkan untuk mempertahankan.</small>@endif
@elseif(isset($choices[$name]))<select name="{{ $name }}"><option value="">Pilih…</option>@foreach($choices[$name] as $choice)<option value="{{ $choice->id }}" @selected($value==$choice->id)>{{ $choice->label() }}</option>@endforeach</select>
@elseif(isset($field['options']))<select name="{{ $name }}">@foreach($field['options'] as $option)<option value="{{ $option }}" @selected($value===$option)>{{ $option }}</option>@endforeach</select>
@elseif(in_array($field['type'],['TEXT','LONGTEXT']))<textarea name="{{ $name }}" rows="{{ $name==='content'?12:4 }}">{{ $value }}</textarea>
@else<input name="{{ $name }}" type="{{ $name==='password'?'password':($name==='email'?'email':(in_array($field['type'],['INT','TINYINT','YEAR'])?'number':($field['type']==='DATE'?'date':($field['type']==='TIME'?'time':($field['type']==='DATETIME'?'datetime-local':'text'))))) }}" value="{{ $name==='password'?'':$value }}" @if(isset($field['length'])) maxlength="{{ $field['length'] }}" @endif @if($name==='password') autocomplete="new-password" @endif>
@endif @error($name)<small class="danger">{{ $message }}</small>@enderror</label>@endforeach
<div class="actions full"><button class="button">Simpan Data</button><a href="{{ url('admin/'.$module) }}">Batal</a><small>* Wajib diisi. Gambar maksimal 5 MB; PDF/Word maksimal 64 MB. Konten mendukung HTML dasar.</small></div></form>@endsection
