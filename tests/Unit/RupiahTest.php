<?php

namespace Tests\Unit;

use App\Support\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    public function test_format_uses_dot_thousands_separator(): void
    {
        $this->assertSame('Rp 50.000', Rupiah::format(50000));
        $this->assertSame('Rp 0', Rupiah::format(0));
    }

    public function test_range_variants(): void
    {
        $this->assertSame('Gratis', Rupiah::range(0, 0));
        $this->assertSame('Rp 15.000', Rupiah::range(15000, 15000));
        $this->assertSame('Rp 50.000 – Rp 120.000', Rupiah::range(50000, 120000));
    }
}
