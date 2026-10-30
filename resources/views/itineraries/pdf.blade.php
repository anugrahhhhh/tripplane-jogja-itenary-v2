<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $itinerary->title }}</title>
    <style>
        body { font-family: sans-serif; color: #3A3A3A; font-size: 12px; }
        h1 { color: #2F6B5A; font-size: 20px; margin-bottom: 0; }
        p.subtitle { color: #6b6b6b; margin-top: 4px; }
        h2 { color: #2F6B5A; font-size: 14px; margin-top: 22px; border-bottom: 1px solid #D4E5DF; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #EAF2EF; font-size: 11px; vertical-align: top; }
        th { background-color: #EAF2EF; color: #275A4C; }
        .travel { color: #8a8a8a; font-size: 10px; }
        .warning { color: #b45309; font-size: 10px; }
        .footer { margin-top: 24px; font-size: 10px; color: #9a9a9a; }
    </style>
</head>
<body>
    <h1>{{ $itinerary->title }}</h1>
    <p class="subtitle">
        {{ $itinerary->start_date->translatedFormat('d F Y') }} - {{ $itinerary->end_date->translatedFormat('d F Y') }}
        &middot; JogjaTrip
    </p>

    @include('itineraries.partials.cost-summary', ['cost' => $cost, 'pdf' => true])

    @foreach ($itinerary->itemsByDay() as $day => $items)
        <h2>Hari ke-{{ $day }} &mdash; {{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('d F Y') }}</h2>

        @if ($items->isEmpty())
            <p>Belum ada destinasi di hari ini.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Jam</th>
                        <th>Destinasi</th>
                        <th>Alamat</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }} - {{ $item->endTime()->format('H:i') }}</td>
                            <td>
                                {{ $item->destination->name }}
                                @if (isset($travelInfoByDay[$day][$item->id]))
                                    <br><span class="travel">&plusmn;{{ $travelInfoByDay[$day][$item->id]['distance_km'] }} km dari destinasi sebelumnya (&asymp;{{ $travelInfoByDay[$day][$item->id]['duration_minutes'] }} menit)</span>
                                @endif
                                @foreach ($warningsByDay[$day][$item->id] ?? [] as $warning)
                                    <br><span class="warning">&#9888; {{ $warning }}</span>
                                @endforeach
                            </td>
                            <td>{{ $item->destination->address }}</td>
                            <td>{{ $item->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <p class="footer">Dibuat dengan JogjaTrip &middot; Estimasi jarak &amp; waktu tempuh bersifat perkiraan garis lurus, bukan rute jalan sesungguhnya.</p>
</body>
</html>
