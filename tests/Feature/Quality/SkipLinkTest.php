<?php

namespace Tests\Feature\Quality;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkipLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_layout_has_skip_link_to_main_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="#konten-utama"', false)
            ->assertSee('Lewati ke konten utama')
            ->assertSee('<main id="konten-utama"', false);
    }

    public function test_catalog_page_has_skip_link(): void
    {
        $this->get('/katalog')
            ->assertOk()
            ->assertSee('href="#konten-utama"', false)
            ->assertSee('<main id="konten-utama"', false);
    }
}
