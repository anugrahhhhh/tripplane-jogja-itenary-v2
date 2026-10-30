<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdleTimeoutMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_page_includes_idle_component(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('home'));

        $response->assertOk()
            ->assertSee('x-data="idleTimeout"', false)
            ->assertSee('data-timeout="120"', false)
            ->assertSee('data-login-url="'.route('login').'"', false)
            ->assertSee('data-ping-url="'.route('session.ping').'"', false)
            ->assertSee('id="idle-logout-form"', false);
    }

    public function test_guest_page_does_not_include_idle_component(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('x-data="idleTimeout"', false);
    }

    public function test_admin_page_includes_idle_component_with_admin_login_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('x-data="idleTimeout"', false)
            ->assertSee('data-login-url="'.route('admin.login').'"', false);
    }
}
