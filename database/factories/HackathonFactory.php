<?php

namespace Database\Factories;

use App\Models\Hackathon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class HackathonFactory extends Factory
{
    protected $model = Hackathon::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'image_path' => 'test/image.jpg',
            'format' => $this->faker->randomElement(['online', 'offline', 'hybrid']),
            'type' => $this->faker->randomElement(['team', 'individual']),
            'min_team_size' => $this->faker->numberBetween(1, 2),
            'max_team_size' => $this->faker->numberBetween(2, 10),
            'registration_start' => Carbon::now(),
            'registration_end' => Carbon::now()->add(10, 'day'),
            'event_start' => Carbon::now()->addMonth(),
            'event_end' => Carbon::now()->addMonths(2),
            'prize_pool' => $this->faker->randomFloat('2', 10000, 1000000),
            'slug' => $this->faker->slug(),
            'is_published' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
