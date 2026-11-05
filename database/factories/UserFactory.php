<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Default: 2FA aktif, supaya test yang memakai actingAs tidak terblokir middleware 2FA.
            'two_factor_secret' => app(TwoFactorAuthenticator::class)->generateSecret(),
            'two_factor_recovery_codes' => [],
            'two_factor_confirmed_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has not set up two-factor authentication.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_step' => null,
        ]);
    }

    /**
     * Store hashed versions of the given plain recovery codes.
     *
     * @param  list<string>  $plain
     */
    public function withRecoveryCodes(array $plain): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $plain),
        ]);
    }
}
