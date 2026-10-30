<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\NewLoginNotification;
use App\Notifications\RecoveryCodeUsedNotification;
use App\Services\Auth\TrustedDeviceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function otp(User $user): string
    {
        return (new Google2FA)->getCurrentOtp($user->two_factor_secret);
    }

    /** Lolos password sehingga pending challenge tersimpan di session. */
    private function passPassword(User $user, ?string $intended = null): void
    {
        if ($intended) {
            $this->withSession(['url.intended' => $intended]);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));
    }

    public function test_login_with_2fa_and_untrusted_device_goes_to_challenge_and_is_not_authenticated(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame($user->id, session('two_factor.pending.user_id'));
        $this->get(route('two-factor.challenge'))->assertOk();
    }

    public function test_login_without_2fa_logs_in_and_redirects_to_setup(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $this->withSession(['url.intended' => route('profile.edit')])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.setup'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(route('profile.edit'), session('url.intended'));
    }

    public function test_login_with_trusted_device_logs_in_directly_without_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->trustedDevices()->create([
            'token_hash' => hash('sha256', 'tokentokentoken'),
            'expires_at' => now()->addDays(10),
        ]);

        $this->withCookie(TrustedDeviceManager::COOKIE, 'tokentokentoken')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_correct_code_logs_in_sends_new_login_notification_and_redirects_to_intended(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->passPassword($user, route('profile.edit'));

        $this->post(route('two-factor.challenge.store'), ['code' => $this->otp($user)])
            ->assertRedirect(route('profile.edit'));

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('two_factor.pending'));
        Notification::assertSentTo($user, NewLoginNotification::class);
        Notification::assertNotSentTo($user, RecoveryCodeUsedNotification::class);
        $this->assertSame(0, TrustedDevice::count());
    }

    public function test_wrong_code_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->passPassword($user);

        $this->from(route('two-factor.challenge'))
            ->post(route('two-factor.challenge.store'), ['code' => '000000'])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHasErrors(['code' => 'Kode salah.']);

        $this->assertGuest();
        $this->assertNotNull(session('two_factor.pending'));
        Notification::assertNothingSent();
    }

    public function test_clock_hint_is_shown_for_wrong_totp_but_not_for_wrong_recovery_code(): void
    {
        $user = User::factory()->withRecoveryCodes(['aaaaa-11111'])->create();
        $this->passPassword($user);
        $hint = 'Pastikan jam HP kamu disetel otomatis.';

        $this->post(route('two-factor.challenge.store'), ['code' => '000000']);
        $this->assertContains($hint, session('errors')->get('code'));

        $this->post(route('two-factor.challenge.store'), ['recovery_code' => 'zzzzz-99999']);
        $this->assertSame(['Kode salah.'], session('errors')->get('code'));
    }

    public function test_attempts_are_counted_before_verification_and_cleared_on_success(): void
    {
        $user = User::factory()->create();
        $this->passPassword($user);
        $key = 'two-factor:'.$user->id.'|127.0.0.1';

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('two-factor.challenge.store'), ['code' => '000000']);
        }
        $this->assertSame(4, RateLimiter::attempts($key));

        $this->post(route('two-factor.challenge.store'), ['code' => $this->otp($user)])->assertRedirect();
        $this->assertSame(0, RateLimiter::attempts($key));
    }

    public function test_replayed_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $code = $this->otp($user);

        $this->passPassword($user);
        $this->post(route('two-factor.challenge.store'), ['code' => $code])->assertRedirect();
        $this->assertAuthenticated();

        Auth::logout();
        $this->flushSession();

        $this->passPassword($user);
        $this->post(route('two-factor.challenge.store'), ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_logs_in_and_sends_recovery_code_used_notification(): void
    {
        Notification::fake();
        $user = User::factory()->withRecoveryCodes(['aaaaa-11111', 'bbbbb-22222'])->create();
        $this->passPassword($user);

        $this->post(route('two-factor.challenge.store'), ['recovery_code' => ' AAAAA-11111 '])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, NewLoginNotification::class);
        Notification::assertSentTo(
            $user,
            RecoveryCodeUsedNotification::class,
            fn ($n) => $n->toArray($user)['remaining'] === 1
        );
    }

    public function test_trust_device_checkbox_creates_trusted_device(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->passPassword($user);

        $response = $this->post(route('two-factor.challenge.store'), [
            'code' => $this->otp($user),
            'trust_device' => '1',
        ]);

        $this->assertSame(1, TrustedDevice::where('user_id', $user->id)->count());
        $response->assertCookie(TrustedDeviceManager::COOKIE);
    }

    public function test_sixth_attempt_within_a_minute_is_throttled(): void
    {
        $user = User::factory()->create();
        $this->passPassword($user);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.challenge.store'), ['code' => '000000'])
                ->assertSessionHasErrors(['code' => 'Kode salah.']);
        }

        // Kode benar pun ditolak selama dikunci
        $this->post(route('two-factor.challenge.store'), ['code' => $this->otp($user)])
            ->assertSessionHasErrors('code');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('code'));
        $this->assertGuest();
    }

    public function test_expired_pending_redirects_to_login_with_message(): void
    {
        $user = User::factory()->create();
        $this->passPassword($user);

        $this->travel(6)->minutes();

        $this->get(route('two-factor.challenge'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Waktu habis, silakan masuk lagi.']);

        $this->assertNull(session('two_factor.pending'));
    }

    public function test_expired_pending_on_submit_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $this->passPassword($user);

        $this->travel(6)->minutes();

        $this->post(route('two-factor.challenge.store'), ['code' => $this->otp($user)])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pending_for_deleted_or_reset_user_redirects_to_login(): void
    {
        $deleted = User::factory()->create();
        $this->passPassword($deleted);
        $deleted->delete();

        $this->get(route('two-factor.challenge'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Waktu habis, silakan masuk lagi.']);

        $this->flushSession();

        $reset = User::factory()->create();
        $this->passPassword($reset);
        $reset->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();

        $this->post(route('two-factor.challenge.store'), ['code' => '123456'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_expired_admin_pending_redirects_to_admin_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['two_factor.pending' => [
            'user_id' => $admin->id,
            'method' => 'password',
            'intended' => '/',
            'expires_at' => now()->subMinute()->timestamp,
        ]]);

        $this->get(route('two-factor.challenge'))->assertRedirect(route('admin.login'));
    }

    public function test_challenge_without_pending_redirects_to_login(): void
    {
        $this->get(route('two-factor.challenge'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Waktu habis, silakan masuk lagi.']);
    }

    public function test_exactly_one_of_code_or_recovery_code_is_required(): void
    {
        $user = User::factory()->create();
        $this->passPassword($user);

        $this->post(route('two-factor.challenge.store'), [])->assertSessionHasErrors('code');
        $this->post(route('two-factor.challenge.store'), ['code' => '123456', 'recovery_code' => 'aaaaa-11111'])
            ->assertSessionHasErrors('code');
        $this->post(route('two-factor.challenge.store'), ['code' => str_repeat('1', 65)])
            ->assertSessionHasErrors('code');
    }

    public function test_login_never_sets_remember_cookie(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => 'on']);

        $response->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    public function test_login_form_has_no_remember_checkbox(): void
    {
        $this->get('/login')->assertDontSee('name="remember"', false);
        $this->get(route('admin.login'))->assertDontSee('name="remember"', false);
    }
}
