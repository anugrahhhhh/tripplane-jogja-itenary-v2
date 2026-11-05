<?php

namespace Tests\Feature\Auth\TwoFactor;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RememberTokenInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_recaller_cookie_stops_working_after_migration(): void
    {
        $user = User::factory()->create(['remember_token' => 'oldRememberToken123']);
        $recaller = $user->id.'|oldRememberToken123|'.$user->getAuthPassword();
        $name = Auth::guard()->getRecallerName();

        // Kontrol: sebelum migrasi cookie lama memang me-login-kan user.
        $this->withCookie($name, $recaller)->get(route('profile.edit'))->assertOk();
        $this->assertAuthenticatedAs($user);

        $this->flushSession();
        Auth::forgetGuards();

        (include database_path('migrations/2026_10_30_000003_invalidate_remember_tokens.php'))->up();

        $this->withCookie($name, $recaller)
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
