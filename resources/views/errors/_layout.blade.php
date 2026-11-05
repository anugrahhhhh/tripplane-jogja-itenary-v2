{{--
    Kerangka halaman error JogjaTrip.

    Sengaja berdiri sendiri (CSS inline, tanpa @vite, tanpa layout utama,
    tanpa query database) supaya tetap tampil walau sumber error-nya adalah
    database, aset build, atau sesi yang rusak.
--}}
@php
    $isAdminArea = request()->is('admin', 'admin/*');
    $homeUrl = url($isAdminArea ? '/admin' : '/');
    $homeLabel = $isAdminArea ? 'Ke Dashboard Admin' : 'Ke Beranda';
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>@yield('title') — JogjaTrip</title>
        <link rel="icon" href="{{ url('/icons/icon-192.png') }}">
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px 16px;
                background: #FAF8F3;
                color: #3A3A3A;
                font-family: 'Figtree', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.5;
            }
            main {
                width: 100%;
                max-width: 30rem;
                text-align: center;
            }
            .logo {
                margin: 0 0 2rem;
                font-size: 1.5rem;
                font-weight: 700;
                color: #2F6B5A;
                text-decoration: none;
                display: inline-block;
            }
            .logo span { color: #B56E80; }
            .code {
                margin: 0;
                font-size: clamp(4rem, 18vw, 6.5rem);
                font-weight: 800;
                line-height: 1;
                letter-spacing: -0.04em;
                color: #A9CBBF;
            }
            h1 {
                margin: 1rem 0 0.5rem;
                font-size: 1.375rem;
                color: #1F483D;
            }
            p.message {
                margin: 0 auto;
                max-width: 26rem;
                font-size: 0.95rem;
                color: #3A3A3Acc;
            }
            .actions {
                margin-top: 1.75rem;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.75rem;
            }
            .btn {
                display: inline-block;
                border-radius: 0.75rem;
                padding: 0.7rem 1.4rem;
                font: inherit;
                font-size: 0.875rem;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                border: 1px solid transparent;
            }
            .btn-primary { background: #2F6B5A; color: #fff; }
            .btn-primary:hover { background: #1F483D; }
            .btn-secondary { background: #fff; color: #2F6B5A; border-color: #D4E5DF; }
            .btn-secondary:hover { background: #EAF2EF; }
            .btn:focus-visible { outline: 3px solid #B56E80; outline-offset: 2px; }
        </style>
    </head>
    <body>
        <main>
            <a href="{{ $homeUrl }}" class="logo" aria-label="JogjaTrip, ke halaman utama">Jogja<span>Trip</span></a>
            <p class="code" aria-hidden="true">@yield('code')</p>
            <h1>@yield('heading')</h1>
            <p class="message">@yield('message')</p>
            <div class="actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a href="{{ $homeUrl }}" class="btn btn-primary">{{ $homeLabel }}</a>
                    <button type="button" class="btn btn-secondary" onclick="history.length > 1 ? history.back() : location.assign('{{ $homeUrl }}')">Kembali</button>
                @endif
            </div>
        </main>
    </body>
</html>
