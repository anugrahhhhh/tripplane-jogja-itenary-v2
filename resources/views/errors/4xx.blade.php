@extends('errors._layout')

@php
    // Cadangan untuk kode 4xx yang tidak punya halaman sendiri (405, 422, dst).
    // Hanya pesan 422 yang ditampilkan karena itu ditulis sendiri lewat
    // abort_if(..., 422, '...'); pesan 4xx lain bisa berisi detail teknis.
    $status = isset($exception) ? $exception->getStatusCode() : 400;
    $customMessage = $status === 422 ? trim((string) $exception->getMessage()) : '';
@endphp

@section('title', 'Permintaan Tidak Dapat Diproses')
@section('code', (string) $status)
@section('heading', 'Permintaan tidak dapat diproses')
@section('message', $customMessage !== ''
    ? $customMessage
    : 'Permintaanmu tidak bisa kami proses. Kembali ke halaman sebelumnya lalu coba lagi.')
