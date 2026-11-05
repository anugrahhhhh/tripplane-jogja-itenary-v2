@extends('errors._layout')

@section('title', 'Layanan Tidak Tersedia')
@section('code', isset($exception) ? (string) $exception->getStatusCode() : '500')
@section('heading', 'Layanan sedang bermasalah')
@section('message', 'Maaf, server sedang tidak bisa melayani permintaanmu. Coba lagi beberapa saat lagi.')

@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()">Coba Lagi</button>
    <a href="{{ url('/') }}" class="btn btn-secondary">Ke Beranda</a>
@endsection
