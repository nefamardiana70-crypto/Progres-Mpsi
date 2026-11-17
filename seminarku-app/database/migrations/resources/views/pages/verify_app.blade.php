@extends('layouts.app', ['title' => 'Verifikasi Sertifikat', 'active' => 'verify', 'sub' => 'Periksa keaslian sertifikat dari kode verifikasinya'])

@section('content')
@include('pages.verify_body')
<?php if ($code === ''): ?>
<div class="panel" style="margin-top:16px"><h3>Cara memeriksa</h3><div class="how"><div><b>Cari kodenya</b>Kode ada di bagian bawah sertifikat, berawalan CERT-.</div><div><b>Masukkan kode</b>Ketik kode pada kolom di atas lalu tekan Periksa.</div><div><b>Baca hasilnya</b>Bila terdaftar, nama pemilik dan acara akan tampil.</div></div></div>
<?php endif; ?>
@endsection
