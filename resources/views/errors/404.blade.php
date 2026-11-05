@extends('errors._layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('code', '404')
@section('heading', 'Halaman tidak ditemukan')
@section('message', 'Halaman yang kamu cari tidak ada, sudah dipindahkan, atau destinasinya sudah tidak aktif. Coba cari dari katalog destinasi.')

@section('actions')
    <a href="{{ url(request()->is('admin', 'admin/*') ? '/admin' : '/') }}" class="btn btn-primary">{{ request()->is('admin', 'admin/*') ? 'Ke Dashboard Admin' : 'Ke Beranda' }}</a>
    @unless (request()->is('admin', 'admin/*'))
        <a href="{{ url('/katalog') }}" class="btn btn-secondary">Lihat Katalog</a>
    @endunless
@endsection
