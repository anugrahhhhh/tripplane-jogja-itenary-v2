<?php

namespace App\Services;

use App\Support\CircuitBreaker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherService
{
    /**
     * Kode cuaca WMO (dipakai Open-Meteo) dipetakan ke label Bahasa
     * Indonesia + ikon emoji. Referensi: https://open-meteo.com/en/docs
     */
    private const WEATHER_LABELS = [
        0 => ['Cerah', '☀️'],
        1 => ['Cerah Berawan', '🌤️'],
        2 => ['Berawan Sebagian', '⛅'],
        3 => ['Berawan', '☁️'],
        45 => ['Berkabut', '🌫️'],
        48 => ['Berkabut', '🌫️'],
        51 => ['Gerimis Ringan', '🌦️'],
        53 => ['Gerimis', '🌦️'],
        55 => ['Gerimis Lebat', '🌧️'],
        56 => ['Gerimis Beku', '🌧️'],
        57 => ['Gerimis Beku Lebat', '🌧️'],
        61 => ['Hujan Ringan', '🌧️'],
        63 => ['Hujan', '🌧️'],
        65 => ['Hujan Lebat', '🌧️'],
        66 => ['Hujan Beku', '🌨️'],
        67 => ['Hujan Beku Lebat', '🌨️'],
        71 => ['Salju Ringan', '🌨️'],
        73 => ['Salju', '🌨️'],
        75 => ['Salju Lebat', '❄️'],
        77 => ['Butiran Salju', '❄️'],
        80 => ['Hujan Lokal Ringan', '🌦️'],
        81 => ['Hujan Lokal', '🌧️'],
        82 => ['Hujan Lokal Lebat', '⛈️'],
        85 => ['Salju Lokal Ringan', '🌨️'],
        86 => ['Salju Lokal Lebat', '🌨️'],
        95 => ['Badai Petir', '⛈️'],
        96 => ['Badai Petir + Hujan Es Ringan', '⛈️'],
        99 => ['Badai Petir + Hujan Es Lebat', '⛈️'],
    ];

    /**
     * Ambil prakiraan cuaca harian (Open-Meteo, gratis tanpa API key) untuk
     * rentang tanggal di satu titik koordinat. Mengembalikan array kosong
     * kalau gagal atau tanggalnya di luar jangkauan prakiraan (API gratis
     * hanya menyediakan prakiraan ±16 hari) — cuaca hanya pelengkap, bukan
     * data wajib supaya halaman itinerary tetap berfungsi tanpanya.
     *
     * @return array<string, array{label: string, icon: string, temp_min: float|null, temp_max: float|null, precipitation_probability: int|null}>
     */
    public function forecastForDateRange(float $lat, float $lng, Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = sprintf(
            'weather:%s,%s:%s:%s',
            round($lat, 2),
            round($lng, 2),
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        $cached = $this->cacheGet($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        // Ketahanan: sirkuit terbuka (Open-Meteo sedang gangguan) -> tidak
        // usah menunggu timeout, langsung pakai data terakhir yang ada.
        $breaker = CircuitBreaker::for('weather');

        if (! $breaker->allows()) {
            return $this->staleOrEmpty($cacheKey);
        }

        $fresh = $this->fetch($lat, $lng, $startDate, $endDate);

        if ($fresh === null) {
            $breaker->recordFailure();
            $fallback = $this->staleOrEmpty($cacheKey);
            // Kegagalan hanya di-cache sebentar supaya cepat pulih.
            $this->cachePut($cacheKey, $fallback, now()->addMinutes(2));

            return $fallback;
        }

        $breaker->recordSuccess();
        $this->cachePut($cacheKey, $fresh, now()->addMinutes(45));

        if ($fresh !== []) {
            $this->cachePut('stale:'.$cacheKey, $fresh, now()->addHours(config('resilience.weather_stale_hours', 72)));
        }

        return $fresh;
    }

    /**
     * Ambil prakiraan dari Open-Meteo. null = layanan gagal (dihitung oleh
     * circuit breaker); array kosong = layanan sehat tapi tidak ada data.
     *
     * @return array<string, array<string, mixed>>|null
     */
    private function fetch(float $lat, float $lng, Carbon $startDate, Carbon $endDate): ?array
    {
        try {
            $response = Http::timeout(10)->connectTimeout(4)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lng,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                'timezone' => 'Asia/Jakarta',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Gagal mengambil prakiraan cuaca', ['message' => $e->getMessage()]);

            return null;
        }

        if ($response->serverError() || $response->status() === 429) {
            Log::warning('Panggilan Open-Meteo gagal', ['status' => $response->status()]);

            return null;
        }

        // 4xx lain (mis. tanggal di luar jangkauan prakiraan) bukan gangguan layanan.
        if ($response->failed()) {
            return [];
        }

        $daily = $response->json('daily');

        if (! is_array($daily) || ! isset($daily['time']) || ! is_array($daily['time'])) {
            return [];
        }

        $result = [];

        foreach ($daily['time'] as $index => $date) {
            $code = $daily['weather_code'][$index] ?? null;
            [$label, $icon] = self::WEATHER_LABELS[$code] ?? ['Tidak diketahui', '🌡️'];

            $result[$date] = [
                'label' => $label,
                'icon' => $icon,
                'temp_min' => $daily['temperature_2m_min'][$index] ?? null,
                'temp_max' => $daily['temperature_2m_max'][$index] ?? null,
                'precipitation_probability' => $daily['precipitation_probability_max'][$index] ?? null,
            ];
        }

        return $result;
    }

    private function staleOrEmpty(string $cacheKey): array
    {
        $stale = $this->cacheGet('stale:'.$cacheKey);

        return is_array($stale) ? $stale : [];
    }

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function cachePut(string $key, mixed $value, mixed $ttl): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
