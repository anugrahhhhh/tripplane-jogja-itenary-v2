@extends('errors._layout')

@section('title', 'Terjadi Kesalahan')
@section('code', '500')
@section('heading', 'Ada yang tidak beres di server')
@section('message', 'Maaf, terjadi kesalahan saat memproses permintaanmu. Kesalahan ini sudah tercatat. Coba lagi beberapa saat lagi.')

@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()">Coba Lagi</button>
    <a href="{{ url('/') }}" class="btn btn-secondary">Ke Beranda</a>
@endsection
