@extends('errors._layout')

@section('title', 'Sedang Perawatan')
@section('code', '503')
@section('heading', 'JogjaTrip sedang dalam perawatan')
@section('message', 'Kami sedang melakukan pembaruan supaya JogjaTrip makin nyaman dipakai. Silakan kembali beberapa menit lagi.')

@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()">Muat Ulang</button>
@endsection
