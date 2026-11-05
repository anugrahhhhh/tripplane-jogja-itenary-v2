<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $password): TestResponse
    {
        return $this->from('/register')->post('/register', [
            'name' => 'Uji Sandi',
            'email' => 'sandi'.uniqid().'@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public static function weakPasswords(): array
    {
        return [
            'hanya huruf' => ['passwordku'],
            'hanya angka' => ['12345678'],
            'terlalu pendek' => ['abc12'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_weak_passwords_are_rejected_on_register(string $password): void
    {
        $this->register($password)->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_letters_and_numbers_are_accepted(): void
    {
        $this->register('jogjatrip2026')->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_reset_password_uses_the_same_rule(): void
    {
        $user = User::factory()->create();
        $token = app('auth.password.broker')->createToken($user);

        $this->from('/reset-password/'.$token)->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'hanyahuruf',
            'password_confirmation' => 'hanyahuruf',
        ])->assertSessionHasErrors('password');
    }

    public function test_change_password_uses_the_same_rule(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => '87654321',
            'password_confirmation' => '87654321',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_existing_users_with_old_passwords_can_still_log_in(): void
    {
        // Aturan baru hanya berlaku saat membuat/mengganti sandi, bukan login.
        $user = User::factory()->withoutTwoFactor()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_forms_show_the_requirement_hint(): void
    {
        $this->get('/register')
            ->assertSee('Minimal 8 karakter, berisi huruf dan angka.')
            ->assertSee('aria-describedby="password-hint"', false);

        $this->get('/reset-password/token-apa-saja?email=a@example.test')
            ->assertSee('Minimal 8 karakter, berisi huruf dan angka.');
    }
}
