@extends('errors._layout')

@section('title', 'Perlu Masuk')
@section('code', '401')
@section('heading', 'Kamu perlu masuk dulu')
@section('message', 'Halaman ini hanya bisa dibuka setelah kamu masuk ke akun JogjaTrip.')

@section('actions')
    <a href="{{ url(request()->is('admin', 'admin/*') ? '/admin/login' : '/login') }}" class="btn btn-primary">Masuk</a>
    <a href="{{ url('/') }}" class="btn btn-secondary">Ke Beranda</a>
@endsection
