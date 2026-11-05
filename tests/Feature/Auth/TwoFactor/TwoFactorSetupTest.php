<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\User;
use App\Notifications\TwoFactorEnabledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorSetupTest extends TestCase
{
    use RefreshDatabase;

    private function userWithoutTwoFactor(array $attributes = []): User
    {
        return User::factory()->withoutTwoFactor()->create($attributes);
    }

    private function validCode(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }

    public function test_user_without_2fa_is_redirected_to_setup_from_any_page(): void
    {
        $user = $this->userWithoutTwoFactor();

        $this->actingAs($user)->get(route('favorites.index'))
            ->assertRedirect(route('two-factor.setup'));

        $admin = $this->userWithoutTwoFactor(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_json_request_without_2fa_gets_403(): void
    {
        $this->actingAs($this->userWithoutTwoFactor())
            ->getJson(route('favorites.index'))
            ->assertStatus(403)
            ->assertExactJson(['reason' => 'two_factor_setup_required']);
    }

    public function test_logout_is_allowed_without_2fa(): void
    {
        $this->actingAs($this->userWithoutTwoFactor())
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_setup_page_shows_qr_and_secret(): void
    {
        $this->actingAs($this->userWithoutTwoFactor())
            ->get(route('two-factor.setup'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('autocomplete="one-time-code"', false);
    }

    public function test_reloading_setup_page_keeps_same_secret(): void
    {
        $this->actingAs($this->userWithoutTwoFactor());

        $this->get(route('two-factor.setup'));
        $first = session('two_factor.setup_secret');
        $this->get(route('two-factor.setup'));

        $this->assertNotNull($first);
        $this->assertSame($first, session('two_factor.setup_secret'));
    }

    public function test_secret_is_not_saved_before_confirmation(): void
    {
        $user = $this->userWithoutTwoFactor();

        $this->actingAs($user)->get(route('two-factor.setup'));

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_wrong_code_shows_error_and_does_not_enable(): void
    {
        $user = $this->userWithoutTwoFactor();

        $this->actingAs($user)->get(route('two-factor.setup'));
        $this->post(route('two-factor.setup.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_post_without_prior_setup_secret_redirects_to_setup(): void
    {
        $this->actingAs($this->userWithoutTwoFactor())
            ->post(route('two-factor.setup.store'), ['code' => '123456'])
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_correct_code_enables_2fa_and_shows_eight_recovery_codes(): void
    {
        Notification::fake();
        $user = $this->userWithoutTwoFactor();

        $this->actingAs($user)->get(route('two-factor.setup'));
        $secret = session('two_factor.setup_secret');

        $this->post(route('two-factor.setup.store'), ['code' => $this->validCode($secret)])
            ->assertRedirect(route('two-factor.recovery-codes'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertSame($secret, $user->two_factor_secret);
        $this->assertNotNull($user->two_factor_last_used_step);
        $this->assertSame(8, $user->recoveryCodesRemaining());
        $this->assertNull(session('two_factor.setup_secret'));

        $plain = session('two_factor.recovery_codes_plain');
        $this->assertCount(8, $plain);

        $response = $this->get(route('two-factor.recovery-codes'))->assertOk();
        foreach ($plain as $code) {
            $response->assertSee($code);
        }
    }

    public function test_enabling_sends_two_factor_enabled_notification(): void
    {
        Notification::fake();
        $user = $this->userWithoutTwoFactor();

        $this->actingAs($user)->get(route('two-factor.setup'));
        $this->post(route('two-factor.setup.store'), [
            'code' => $this->validCode(session('two_factor.setup_secret')),
        ]);

        Notification::assertSentTo($user, TwoFactorEnabledNotification::class);
    }

    public function test_acknowledging_codes_clears_them_and_redirects_to_intended(): void
    {
        Notification::fake();
        $user = $this->userWithoutTwoFactor();

        $this->actingAs($user)->get(route('favorites.index'))
            ->assertRedirect(route('two-factor.setup'));
        $this->get(route('two-factor.setup'));
        $this->post(route('two-factor.setup.store'), [
            'code' => $this->validCode(session('two_factor.setup_secret')),
        ]);

        $this->post(route('two-factor.recovery-codes.ack'))
            ->assertRedirect(route('favorites.index'));

        $this->assertNull(session('two_factor.recovery_codes_plain'));
    }

    public function test_acknowledging_as_admin_defaults_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->withSession(['two_factor.recovery_codes_plain' => ['aaaaa-bbbbb']])
            ->post(route('two-factor.recovery-codes.ack'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_user_with_2fa_visiting_setup_is_redirected_home(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('two-factor.setup'))
            ->assertRedirect(route('home'));
    }

    public function test_recovery_codes_page_without_codes_redirects_to_default(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('two-factor.recovery-codes'))
            ->assertRedirect(route('home'));
    }

    public function test_setup_page_includes_idle_warning_but_guest_pages_do_not(): void
    {
        $this->actingAs($this->userWithoutTwoFactor())
            ->get(route('two-factor.setup'))
            ->assertOk()
            ->assertSee('x-data="idleTimeout"', false);

        auth()->logout();

        $this->get(route('login'))->assertOk()->assertDontSee('x-data="idleTimeout"', false);
    }
}
