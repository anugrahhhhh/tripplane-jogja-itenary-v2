<?php

namespace App\Services\Concerns;

use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use RuntimeException;

trait CallsGeminiApi
{
    /**
     * Kirim prompt ke Gemini API dan kembalikan hasil JSON yang sudah
     * di-decode. Structured output dipaksa lewat responseSchema.
     *
     * Ketahanan:
     * - Model utama dicoba dulu, lalu model cadangan dari keluarga lain
     *   (kapasitas servernya kemungkinan besar terpisah).
     * - 429/503 (sibuk) dicoba ulang sekali di model yang sama karena
     *   biasanya cepat pulih; respons error ini datang cepat.
     * - Timeout/koneksi putus TIDAK dicoba ulang di model yang sama: model
     *   yang lambat kemungkinan masih lambat, jadi langsung pindah cadangan.
     * - 404 (model dihentikan Google) langsung pindah cadangan.
     * - Error lain (400 permintaan salah, 401/403 kredensial) berhenti tanpa
     *   mencoba model lain karena bukan soal kapasitas.
     *
     * Batas waktu total (services.gemini.time_budget) berlaku untuk SELURUH
     * rangkaian percobaan. Setiap percobaan hanya memakai sisa waktu yang ada,
     * sehingga pengguna tidak pernah menunggu lebih lama dari batas itu,
     * sementara model utama tetap mendapat waktu penuh per percobaan.
     *
     * @return array<string, mixed>
     */
    protected function requestGeminiJson(string $prompt, array $responseSchema): array
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            throw new RuntimeException('GEMINI_API_KEY belum diatur di file .env.');
        }

        $budget = $this->geminiTimeBudget();
        $this->extendExecutionTimeFor($budget);

        $deadline = $this->geminiNow() + $budget;
        $attempted = [];
        $lastResponse = null;
        $lastConnectionError = null;
        $budgetExhausted = false;

        foreach ($this->geminiModelChain() as $model) {
            if ($this->geminiRemaining($deadline) < $this->geminiMinAttemptSeconds()) {
                $budgetExhausted = true;
                break;
            }

            $attempted[] = $model;
            $result = $this->attemptGeminiModel($apiKey, $model, $prompt, $responseSchema, $deadline);

            if ($result instanceof ConnectionException) {
                $lastConnectionError = $result;
                $lastResponse = null;

                Log::warning('Koneksi ke model Gemini gagal/timeout, pindah ke model cadangan', [
                    'model_gagal' => $model,
                    'pesan' => $result->getMessage(),
                    'sisa_waktu_detik' => round($this->geminiRemaining($deadline), 1),
                ]);

                continue;
            }

            if ($result->successful()) {
                return $this->decodeGeminiResponse($result);
            }

            $lastResponse = $result;
            $lastConnectionError = null;

            if (! in_array($result->status(), [404, 429, 503], true)) {
                break;
            }

            Log::warning('Model Gemini gagal (sibuk/dihentikan), pindah ke model cadangan', [
                'model_gagal' => $model,
                'status' => $result->status(),
            ]);
        }

        Log::error('Semua model Gemini gagal dihubungi', [
            'models_dicoba' => $attempted,
            'batas_waktu_habis' => $budgetExhausted,
            'batas_waktu_detik' => $budget,
            'status_terakhir' => $lastResponse?->status(),
            'body_terakhir' => $lastResponse?->body(),
            'connection_error_terakhir' => $lastConnectionError?->getMessage(),
        ]);

        if ($budgetExhausted) {
            throw new RuntimeException('Server AI sedang lambat merespons. Coba lagi dalam beberapa saat.');
        }

        throw new RuntimeException('Server AI sedang sangat sibuk. Coba lagi dalam beberapa menit.');
    }

    /**
     * Coba satu model, dengan satu kali coba ulang untuk 429/503 bila sisa
     * waktu masih cukup. Mengembalikan Response terakhir, atau
     * ConnectionException kalau timeout/gagal konek.
     */
    private function attemptGeminiModel(string $apiKey, string $model, string $prompt, array $responseSchema, float $deadline): Response|ConnectionException
    {
        $maxTries = 2;
        $backoffSeconds = 1.0;

        for ($try = 1; ; $try++) {
            $timeout = min($this->geminiAttemptTimeout(), $this->geminiRemaining($deadline));

            try {
                $response = $this->callGeminiModel($apiKey, $model, $prompt, $responseSchema, $timeout);
            } catch (ConnectionException $e) {
                return $e;
            } catch (TransferException $e) {
                // Error jaringan lain yang jarang terjadi (mis. respons
                // terpotong) diperlakukan sama seperti gagal koneksi.
                return new ConnectionException($e->getMessage(), 0, $e);
            }

            $retryable = in_array($response->status(), [429, 503], true);
            $hasTimeForRetry = $this->geminiRemaining($deadline) - $backoffSeconds >= $this->geminiMinAttemptSeconds();

            if ($response->successful() || ! $retryable || $try >= $maxTries || ! $hasTimeForRetry) {
                return $response;
            }

            Sleep::usleep((int) ($backoffSeconds * 1_000_000));
        }
    }

    /**
     * Model utama (dari .env) diikuti model cadangan. Duplikat dibuang
     * supaya tidak mencoba model yang sama dua kali.
     *
     * @return array<int, string>
     */
    private function geminiModelChain(): array
    {
        $primary = config('services.gemini.model');
        $fallbacks = array_filter(array_map('trim', explode(',', (string) config('services.gemini.fallback_models'))));

        return array_values(array_unique(array_filter([$primary, ...$fallbacks])));
    }

    private function callGeminiModel(string $apiKey, string $model, string $prompt, array $responseSchema, float $timeout): Response
    {
        return Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'content-type' => 'application/json',
        ])
            ->timeout($timeout)
            ->connectTimeout(min(5.0, $timeout))
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $responseSchema,
                ],
            ]);
    }

    /**
     * Batas waktu total semua percobaan, dalam detik. Dijaga di 10–150 detik
     * supaya selalu di bawah batas Nginx/PHP-FPM (180 detik).
     */
    private function geminiTimeBudget(): float
    {
        return (float) max(10, min(150, (int) config('services.gemini.time_budget', 50)));
    }

    /**
     * Batas waktu satu percobaan, dalam detik (5 sampai batas total).
     */
    private function geminiAttemptTimeout(): float
    {
        return (float) max(5, min((int) config('services.gemini.attempt_timeout', 20), (int) $this->geminiTimeBudget()));
    }

    /**
     * Percobaan dengan sisa waktu lebih kecil dari ini hampir pasti gagal
     * (Gemini butuh beberapa detik untuk menyusun JSON), jadi tidak dimulai.
     */
    private function geminiMinAttemptSeconds(): float
    {
        return min(8.0, $this->geminiAttemptTimeout());
    }

    private function geminiRemaining(float $deadline): float
    {
        return max(0.0, $deadline - $this->geminiNow());
    }

    /**
     * Waktu sekarang (detik, presisi mikro). Pakai Carbon supaya test bisa
     * mensimulasikan waktu berjalan tanpa benar-benar menunggu.
     */
    private function geminiNow(): float
    {
        return Carbon::now()->getPreciseTimestamp() / 1_000_000;
    }

    /**
     * Pastikan PHP tidak menghentikan request di tengah jalan sebelum batas
     * waktu AI habis (di Windows, max_execution_time dihitung dari waktu
     * nyata, termasuk saat menunggu jawaban Gemini).
     */
    private function extendExecutionTimeFor(float $budget): void
    {
        $current = (int) ini_get('max_execution_time');
        $needed = (int) ceil($budget) + 30;

        if ($current !== 0 && $current < $needed && function_exists('set_time_limit')) {
            @set_time_limit($needed);
        }
    }

    private function decodeGeminiResponse(Response $response): array
    {
        $text = (string) $response->json('candidates.0.content.parts.0.text', '');
        $decoded = json_decode($this->extractJson($text), true);

        if (! is_array($decoded)) {
            Log::error('Respons Gemini bukan JSON valid', ['text' => $text]);

            throw new RuntimeException('Respons AI tidak dapat diproses. Coba lagi.');
        }

        return $decoded;
    }

    private function extractJson(string $text): string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end < $start) {
            return $text;
        }

        return substr($text, $start, $end - $start + 1);
    }
}
