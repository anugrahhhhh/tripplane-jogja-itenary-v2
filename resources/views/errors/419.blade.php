@extends('errors._layout')

@section('title', 'Sesi Kedaluwarsa')
@section('code', '419')
@section('heading', 'Sesi halaman sudah kedaluwarsa')
@section('message', 'Halaman ini terlalu lama dibiarkan terbuka, jadi demi keamanan formulirnya tidak bisa dikirim. Muat ulang halaman lalu coba lagi.')

@section('actions')
    <button type="button" class="btn btn-primary" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Kembali &amp; Coba Lagi</button>
    <a href="{{ url('/') }}" class="btn btn-secondary">Ke Beranda</a>
@endsection
