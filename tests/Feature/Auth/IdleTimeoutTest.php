<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class IdleTimeoutTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Kamu keluar otomatis karena tidak aktif selama 5 menit.';

    public function test_user_is_still_logged_in_after_299_seconds(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(299)->seconds();

        $this->get('/profile')->assertOk();
        $this->assertAuthenticated();
    }

    public function test_user_is_still_logged_in_after_exactly_300_seconds(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(300)->seconds();

        $this->get('/profile')->assertOk();
    }

    public function test_user_is_logged_out_after_301_seconds_with_message(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(301)->seconds();

        $this->get('/profile')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', self::MESSAGE);
        $this->assertGuest();
    }

    public function test_admin_is_redirected_to_admin_login_on_idle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/profile')->assertOk();

        $this->travel(301)->seconds();

        $this->get('/profile')
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status', self::MESSAGE);
        $this->assertGuest();
    }

    public function test_json_request_gets_401_with_reason(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(301)->seconds();

        $this->getJson('/profile')
            ->assertStatus(401)
            ->assertExactJson(['reason' => 'idle_timeout']);
        $this->assertGuest();
    }

    public function test_background_header_does_not_extend_session(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(200)->seconds();
        $this->withHeader('X-Background-Request', '1')->get('/profile')->assertOk();

        $this->travel(101)->seconds();
        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_background_request_is_still_logged_out_when_idle(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(301)->seconds();

        $this->withHeader('X-Background-Request', '1')->getJson('/profile')
            ->assertStatus(401);
        $this->assertGuest();
    }

    public function test_ping_extends_session(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(200)->seconds();
        $this->post(route('session.ping'))->assertNoContent();

        $this->travel(250)->seconds();
        $this->get('/profile')->assertOk();
        $this->assertAuthenticated();
    }

    public function test_ping_requires_authentication(): void
    {
        $this->post(route('session.ping'))->assertRedirect(route('login'));
    }

    public function test_idle_logout_via_logout_form_redirects_to_login_with_message(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'), ['reason' => 'idle'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', self::MESSAGE);
        $this->assertGuest();
    }

    public function test_idle_logout_via_logout_form_for_admin_redirects_to_admin_login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('logout'), ['reason' => 'idle'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status', self::MESSAGE);
    }

    public function test_normal_logout_still_redirects_home(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_timeout_is_configurable(): void
    {
        config(['auth.idle_timeout' => 60]);
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();

        $this->travel(61)->seconds();

        $this->get('/profile')->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Kamu keluar otomatis karena tidak aktif selama 1 menit.');
    }

    public function test_not_me_link_still_works_after_idle_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertOk();
        $link = URL::temporarySignedRoute('security.not-me', now()->addHours(24), ['user' => $user->id]);

        $this->travel(301)->seconds();

        $this->get($link)->assertOk()->assertSee('Ya, amankan akun saya');
        $this->assertGuest();
    }

    public function test_guest_logout_post_without_csrf_token_redirects_to_login(): void
    {
        // CSRF dimatikan otomatis saat testing; paksa aktif agar perilaku produksi teruji
        $this->app['env'] = 'local';

        $this->post(route('logout'), ['reason' => 'idle'])->assertRedirect(route('login'));
    }

    public function test_logout_post_with_stale_csrf_token_does_not_return_419(): void
    {
        $this->app['env'] = 'local';
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'), ['reason' => 'idle', '_token' => 'stale'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
