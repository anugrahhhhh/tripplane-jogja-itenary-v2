<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_logout_clears_site_cache(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }

    public function test_idle_form_logout_clears_site_cache(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'), ['reason' => 'idle'])
            ->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }

    public function test_idle_expiry_redirect_clears_site_cache(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(121)->seconds();

        $this->get('/profile')
            ->assertRedirect(route('login'))
            ->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }

    public function test_idle_expiry_json_clears_site_cache(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(121)->seconds();

        $this->getJson('/profile')
            ->assertStatus(401)
            ->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }

    public function test_idle_expiry_on_not_me_route_clears_site_cache(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertOk();
        $link = URL::temporarySignedRoute('security.not-me', now()->addHours(24), ['user' => $user->id]);

        $this->travel(121)->seconds();

        $this->get($link)->assertOk()->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }

    public function test_not_me_confirm_clears_site_cache(): void
    {
        $user = User::factory()->create();
        $link = URL::temporarySignedRoute('security.not-me.confirm', now()->addHours(24), ['user' => $user->id]);

        $this->post($link)->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }

    public function test_logout_landing_page_runs_page_cache_cleanup(): void
    {
        $this->actingAs(User::factory()->create())
            ->followingRedirects()
            ->post(route('logout'))
            ->assertSee('data-clear-page-cache', false);
    }

    public function test_not_me_done_page_runs_page_cache_cleanup(): void
    {
        $user = User::factory()->create();
        $link = URL::temporarySignedRoute('security.not-me.confirm', now()->addHours(24), ['user' => $user->id]);

        $this->post($link)->assertSee('data-clear-page-cache', false);
    }

    public function test_normal_page_does_not_run_page_cache_cleanup(): void
    {
        $this->get(route('home'))->assertDontSee('data-clear-page-cache', false);
    }

    public function test_account_deletion_clears_site_cache(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/')
            ->assertHeader('Clear-Site-Data', '"cache", "storage"')
            ->assertSessionHas('clear_page_cache', true);
    }

    public function test_sensitive_pages_are_not_cacheable(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([route('profile.edit'), route('notifications.index')] as $url) {
            $cache = $this->get($url)->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cache);
            $this->assertStringContainsString('private', $cache);
        }

        $this->app['auth']->logout();

        $cache = $this->get(route('login'))->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('private', $cache);
    }

    public function test_public_pages_remain_cacheable(): void
    {
        $this->get(route('destinations.index'))->assertOk();

        $this->assertStringNotContainsString(
            'no-store',
            (string) $this->get(route('destinations.index'))->headers->get('Cache-Control')
        );
    }
}
