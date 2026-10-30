<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ServiceWorkerTest extends TestCase
{
    public function test_service_worker_uses_v2_cache(): void
    {
        $this->assertStringContainsString('jogjatrip-v2', file_get_contents(public_path('sw.js')));
    }

    public function test_service_worker_skips_no_store_and_redirects(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('no-store', $sw);
        $this->assertStringContainsString('redirected', $sw);
        $this->assertStringContainsString("response.type === 'basic'", $sw);
        $this->assertStringContainsString("includes('text/html')", $sw);
    }

    public function test_login_pages_include_page_cache_cleanup(): void
    {
        $this->get(route('login'))->assertSee('data-clear-page-cache', false);
        $this->get(route('admin.login'))->assertSee('data-clear-page-cache', false);
    }
}
