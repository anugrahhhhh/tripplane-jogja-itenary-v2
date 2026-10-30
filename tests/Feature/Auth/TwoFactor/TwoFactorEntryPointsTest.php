<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\User;
use App\Services\Auth\TrustedDeviceManager;
use App\Services\Auth\TwoFactorGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class TwoFactorEntryPointsTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email): void
    {
        $socialiteUser = \Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getName')->andReturn('Traveler Google');
        $socialiteUser->shouldReceive('getNickname')->andReturn('Traveler Google');
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    public function test_admin_login_goes_through_challenge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame('password', session('two_factor.pending.method'));
        $this->assertSame(route('admin.dashboard'), session('two_factor.pending.intended'));
    }

    public function test_admin_without_2fa_is_sent_to_setup(): void
    {
        $admin = User::factory()->withoutTwoFactor()->create(['role' => 'admin']);

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.setup'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame(route('admin.dashboard'), session('url.intended'));
    }

    public function test_admin_login_still_rejects_traveler(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);

        $this->post(route('admin.login'), ['email' => $traveler->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun ini bukan akun Admin. Silakan masuk lewat halaman khusus wisatawan.']);

        $this->assertGuest();
        $this->assertNull(session('two_factor.pending'));
    }

    public function test_expired_admin_pending_redirects_to_admin_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->travel(TwoFactorGate::PENDING_TTL_MINUTES + 1)->minutes();

        $this->get(route('two-factor.challenge'))->assertRedirect(route('admin.login'));
    }

    public function test_new_google_user_is_sent_to_setup(): void
    {
        $this->fakeGoogleUser('google-new', 'baru@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('two-factor.setup'));

        $this->assertAuthenticatedAs(User::where('email', 'baru@example.com')->first());
        $this->assertSame(route('home'), session('url.intended'));
    }

    public function test_existing_google_user_with_2fa_goes_to_challenge(): void
    {
        $user = User::factory()->create(['google_id' => 'google-old']);
        $this->fakeGoogleUser('google-old', $user->email);

        $this->get(route('auth.google.callback'))->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame('google', session('two_factor.pending.method'));
    }

    public function test_existing_google_user_with_trusted_device_logs_in_directly(): void
    {
        $user = User::factory()->create(['google_id' => 'google-trusted']);
        $user->trustedDevices()->create([
            'token_hash' => hash('sha256', 'tokentokentoken'),
            'expires_at' => now()->addDays(10),
        ]);
        $this->fakeGoogleUser('google-trusted', $user->email);

        $this->withCookie(TrustedDeviceManager::COOKIE, 'tokentokentoken')
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_never_sets_remember_cookie(): void
    {
        $this->fakeGoogleUser('google-cookie', 'cookie@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('two-factor.setup'))
            ->assertCookieMissing(Auth::guard()->getRecallerName());
    }
}
