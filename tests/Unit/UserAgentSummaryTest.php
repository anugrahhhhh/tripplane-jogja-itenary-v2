<?php

namespace Tests\Unit;

use App\Support\UserAgentSummary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserAgentSummaryTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function userAgents(): array
    {
        return [
            'chrome windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'Chrome di Windows'],
            'edge windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0', 'Edge di Windows'],
            'opera windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0', 'Opera di Windows'],
            'firefox linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0', 'Firefox di Linux'],
            'safari ios' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'Safari di iOS'],
            'safari macos' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15', 'Safari di macOS'],
            'chrome android' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'Chrome di Android'],
            'null' => [null, 'Browser tidak dikenal'],
            'kosong' => ['', 'Browser tidak dikenal'],
            'tidak dikenal' => ['curl/8.0', 'Browser tidak dikenal'],
        ];
    }

    #[DataProvider('userAgents')]
    public function test_summarize(?string $userAgent, string $expected): void
    {
        $this->assertSame($expected, UserAgentSummary::summarize($userAgent));
    }
}
