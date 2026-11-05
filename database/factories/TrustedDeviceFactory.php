<?php

namespace Database\Factories;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrustedDevice>
 */
class TrustedDeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', Str::random(40)),
            'user_agent' => fake()->userAgent(),
            'ip_address' => fake()->ipv4(),
            'last_used_at' => null,
            'expires_at' => now()->addDays(30),
        ];
    }
}
