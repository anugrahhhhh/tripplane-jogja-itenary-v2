<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays($this->faker->numberBetween(1, 10));

        return [
            'user_id' => User::factory(),
            'title' => 'Liburan '.$this->faker->city(),
            'start_date' => $start,
            'end_date' => $start->copy()->addDays(1),
            'traveler_count' => 1,
            'type' => 'planned',
        ];
    }
}
