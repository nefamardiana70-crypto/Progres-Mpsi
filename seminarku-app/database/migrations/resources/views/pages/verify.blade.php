@extends('layouts.public', ['title' => 'Verifikasi Sertifikat'])

@section('content')
<section class="section" style="max-width:760px;min-height:60vh">
    <div class="sec-head"><div><h2>Verifikasi Sertifikat</h2><p class="muted" style="margin:6px 0 0">Masukkan kode verifikasi yang tertera pada sertifikat.</p></div></div>
    @include('pages.verify_body')
</section>
@endsection
