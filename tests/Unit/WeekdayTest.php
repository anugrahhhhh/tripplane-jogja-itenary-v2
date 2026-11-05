<?php

namespace Tests\Unit;

use App\Support\Weekday;
use PHPUnit\Framework\TestCase;

class WeekdayTest extends TestCase
{
    public function test_name_maps_iso_day(): void
    {
        $this->assertSame('Senin', Weekday::name(1));
        $this->assertSame('Minggu', Weekday::name(7));
    }

    public function test_names_sorted_and_joined(): void
    {
        $this->assertSame('Senin, Rabu', Weekday::names([3, 1]));
    }

    public function test_names_empty_for_null_or_empty(): void
    {
        $this->assertSame('', Weekday::names(null));
        $this->assertSame('', Weekday::names([]));
    }
}
