@extends('layouts.admin')
@section('content')<div class="card card-body"><h2>Ekspor Database</h2><p>Unduh seluruh tabel sebagai JSON, termasuk data pengguna. Simpan backup di lokasi pribadi. File unggahan perlu disalin terpisah dari storage/app/public.</p><form method="post" action="{{ url('admin/backup/export') }}">@csrf<button class="button">Unduh Backup</button></form></div>@endsection
