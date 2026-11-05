<?php

namespace Tests\Feature\Quality;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Route::middleware('web')->group(function () {
            Route::get('/_uji/403', fn () => abort(403));
            Route::get('/_uji/403-pesan', fn () => abort(403, 'Pesan izin khusus.'));
            Route::get('/_uji/422', fn () => abort(422, 'Kategori masih memiliki destinasi, tidak bisa dihapus.'));
            Route::get('/_uji/429', fn () => abort(429, '', ['Retry-After' => 42]));
            Route::get('/_uji/500', fn () => throw new RuntimeException('detail rahasia server'));
            Route::get('/_uji/503', fn () => abort(503));
            Route::get('/_uji/502', fn () => abort(502));
            Route::post('/_uji/form', fn () => 'ok');
        });
    }

    public function test_404_page_is_in_indonesian(): void
    {
        $this->get('/tidak-ada-di-sini')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Lihat Katalog');
    }

    public function test_404_for_missing_destination_hides_model_detail(): void
    {
        $this->get('/destinasi/tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertDontSee('No query results');
    }

    public function test_admin_404_links_to_admin_dashboard(): void
    {
        $this->get('/admin/tidak-ada')
            ->assertNotFound()
            ->assertSee('Ke Dashboard Admin')
            ->assertDontSee('Lihat Katalog');
    }

    public function test_403_default_message_is_replaced(): void
    {
        $this->get('/_uji/403')
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('tidak memiliki izin');
    }

    public function test_403_custom_message_is_shown(): void
    {
        $this->get('/_uji/403-pesan')
            ->assertForbidden()
            ->assertSee('Pesan izin khusus.');
    }

    public function test_422_shows_custom_message_via_4xx_fallback(): void
    {
        $this->get('/_uji/422')
            ->assertStatus(422)
            ->assertSee('Kategori masih memiliki destinasi, tidak bisa dihapus.');
    }

    public function test_405_uses_generic_4xx_page_without_technical_detail(): void
    {
        $this->get('/_uji/form')
            ->assertStatus(405)
            ->assertSee('Permintaan tidak dapat diproses')
            ->assertDontSee('not supported');
    }

    public function test_419_page_explains_expired_form(): void
    {
        // Laravel mematikan cek CSRF saat test; paksa aktif dengan token salah.
        $this->app->instance('env', 'local');

        $this->post('/_uji/form', ['_token' => 'token-salah'])
            ->assertStatus(419)
            ->assertSee('Sesi halaman sudah kedaluwarsa');
    }

    public function test_429_page_shows_retry_after(): void
    {
        $this->get('/_uji/429')
            ->assertStatus(429)
            ->assertSee('Coba lagi dalam 42 detik.');
    }

    public function test_500_page_hides_exception_detail(): void
    {
        $this->get('/_uji/500')
            ->assertStatus(500)
            ->assertSee('Ada yang tidak beres di server')
            ->assertDontSee('detail rahasia server');
    }

    public function test_503_maintenance_page(): void
    {
        $this->get('/_uji/503')
            ->assertStatus(503)
            ->assertSee('sedang dalam perawatan');
    }

    public function test_other_5xx_uses_generic_page(): void
    {
        $this->get('/_uji/502')
            ->assertStatus(502)
            ->assertSee('Layanan sedang bermasalah');
    }

    public function test_error_pages_do_not_depend_on_vite_build(): void
    {
        // Halaman error tidak boleh bergantung pada aset build, supaya tetap
        // tampil walau public/build hilang atau rusak.
        $this->get('/tidak-ada-di-sini')->assertDontSee('/build/assets/', false);
    }
}
