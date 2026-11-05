@extends('errors._layout')

@php
    // Pesan dari abort(403, '...') ditulis sendiri dalam bahasa Indonesia,
    // jadi aman ditampilkan. Pesan bawaan framework (bahasa Inggris) diganti.
    $customMessage = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $defaultMessages = ['', 'Forbidden', 'This action is unauthorized.'];
@endphp

@section('title', 'Akses Ditolak')
@section('code', '403')
@section('heading', 'Akses ditolak')
@section('message', in_array($customMessage, $defaultMessages, true)
    ? 'Kamu tidak memiliki izin untuk membuka halaman ini. Kalau ini itinerary milik orang lain, minta pemiliknya menambahkanmu sebagai kolaborator.'
    : $customMessage)
