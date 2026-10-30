<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Password benar belum cukup: user baru login setelah lolos challenge 2FA.
        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_login_rehashes_password_hashed_with_outdated_cost(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password', ['rounds' => 4])]);
        $old = $user->password;
        Hash::driver('bcrypt')->setRounds(6);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $user->refresh();
        $this->assertNotSame($old, $user->password);
        $this->assertFalse(Hash::needsRehash($user->password));
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
