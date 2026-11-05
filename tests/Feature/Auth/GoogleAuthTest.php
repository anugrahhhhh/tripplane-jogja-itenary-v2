<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeGoogleUser(string $id, string $email, string $name = 'Traveler Google'): void
    {
        $socialiteUser = \Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getNickname')->andReturn($name);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    public function test_a_brand_new_google_user_gets_a_traveler_account_created_and_logged_in(): void
    {
        $this->fakeGoogleUser('google-123', 'baru@example.com', 'Wisatawan Baru');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('two-factor.setup'));
        $this->assertAuthenticated();

        $user = User::where('email', 'baru@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('traveler', $user->role);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_an_existing_traveler_with_matching_email_gets_google_id_linked(): void
    {
        $existing = User::factory()->create(['email' => 'sudah@example.com', 'role' => 'traveler', 'google_id' => null]);

        $this->fakeGoogleUser('google-456', 'sudah@example.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertSame('google-456', $existing->fresh()->google_id);
    }

    public function test_a_returning_google_user_is_matched_by_google_id_even_if_email_changed(): void
    {
        $existing = User::factory()->create(['google_id' => 'google-789']);

        $this->fakeGoogleUser('google-789', 'email-baru-di-google@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame($existing->id, session('two_factor.pending.user_id'));
    }

    public function test_an_admin_account_can_never_log_in_via_google(): void
    {
        User::factory()->create(['email' => 'admin@jogjatrip.test', 'role' => 'admin']);

        $this->fakeGoogleUser('google-999', 'admin@jogjatrip.test');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
