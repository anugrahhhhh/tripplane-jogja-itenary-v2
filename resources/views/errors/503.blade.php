@extends('errors._layout')

{{-- $outage = gangguan tak terduga (mis. database putus), selain itu mode perawatan terjadwal. --}}
@section('title', ! empty($outage) ? 'Gangguan Sementara' : 'Sedang Perawatan')
@section('code', '503')
@section('head')
    @if (! empty($outage))
        <meta http-equiv="refresh" content="30">
    @endif
@endsection
@section('heading', ! empty($outage) ? 'JogjaTrip sedang mengalami gangguan sementara' : 'JogjaTrip sedang dalam perawatan')
@section('message', ! empty($outage)
    ? 'Sebagian layanan kami sedang tidak bisa dihubungi. Tim kami sudah menerima peringatan otomatis. Halaman ini akan mencoba memuat ulang dalam 30 detik.'
    : 'Kami sedang melakukan pembaruan supaya JogjaTrip makin nyaman dipakai. Silakan kembali beberapa menit lagi.')

@section('actions')
    <button type="button" class="btn btn-primary" onclick="location.reload()">Muat Ulang</button>
@endsection
