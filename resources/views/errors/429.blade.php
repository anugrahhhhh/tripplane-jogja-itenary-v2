@extends('errors._layout')

@php
    $retryAfter = isset($exception) && method_exists($exception, 'getHeaders')
        ? (int) ($exception->getHeaders()['Retry-After'] ?? 0)
        : 0;
@endphp

@section('title', 'Terlalu Banyak Permintaan')
@section('code', '429')
@section('heading', 'Pelan-pelan dulu, ya')
@section('message', $retryAfter > 0
    ? 'Terlalu banyak permintaan dalam waktu singkat. Coba lagi dalam '.$retryAfter.' detik.'
    : 'Terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar lalu coba lagi.')
