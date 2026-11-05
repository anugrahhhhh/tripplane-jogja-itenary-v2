<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuestFormRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_is_limited_per_ip_with_friendly_message(): void
    {
        for ($i = 0; $i < 10; $i++) {
            // Data sengaja tidak valid supaya tidak ada akun yang benar-benar dibuat.
            $this->from('/register')->post('/register', ['email' => "bukan-email-$i"]);
        }

        $response = $this->from('/register')->post('/register', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'password' => 'rahasia-sekali',
            'password_confirmation' => 'rahasia-sekali',
        ]);

        $response->assertRedirect('/register')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', 'bot@example.com')
            ->assertSessionMissing('_old_input.password');

        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    public function test_register_still_works_below_the_limit(): void
    {
        for ($i = 0; $i < 9; $i++) {
            $this->from('/register')->post('/register', ['email' => "bukan-email-$i"]);
        }

        $this->post('/register', [
            'name' => 'Wisatawan',
            'email' => 'wisatawan@example.com',
            'password' => 'password-kuat-123',
            'password_confirmation' => 'password-kuat-123',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'wisatawan@example.com']);
    }

    public function test_forgot_password_is_limited_per_ip(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => "orang$i@example.com"]);
        }

        $user = User::factory()->create();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');

        Notification::assertNothingSentTo($user);
    }

    public function test_limits_are_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
                ->post('/forgot-password', ['email' => "orang$i@example.com"]);
        }

        // IP lain tidak ikut terblokir (boleh dapat error lain, misal email tidak terdaftar).
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'lain@example.com'])
            ->assertRedirect('/forgot-password');

        $errors = session('errors');
        $this->assertFalse($errors !== null && str_contains((string) $errors->first('email'), 'Terlalu banyak'));
    }

    public function test_reset_password_is_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->from('/reset-password/token')->post('/reset-password', ['token' => 'x', 'email' => 'a@example.com']);
        }

        $this->from('/reset-password/token')
            ->post('/reset-password', ['token' => 'x', 'email' => 'a@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
    }
}
