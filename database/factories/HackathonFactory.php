<?php

namespace Database\Factories;

use App\Models\Hackathon;
use App\Models\Role;
use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class HackathonFactory extends Factory
{
    protected $model = Hackathon::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(3);
        $prize_type = $this->faker->randomElement(['cash', 'non-cash']);
        return [
            'user_id' => Role::ORGANIZER,
            'title' => $title,
            'format' => $this->faker->randomElement(['online', 'offline', 'hybrid']),
            'type' => $this->faker->randomElement(['team', 'individual']),
            'min_team_size' => $this->faker->numberBetween(1, 2),
            'max_team_size' => $this->faker->numberBetween(2, 10),
            'registration_start' => Carbon::now(),
            'registration_end' => Carbon::now()->add(10, 'day'),
            'event_start' => Carbon::now()->submonth(),
            'event_end' => Carbon::now()->subDay(),
            'prize_type' => $prize_type,
            'prize_pool' => $prize_type === 'cash' ? round($this->faker->numberBetween(10000, 1000000), -3) : $this->faker->numberBetween(1,10),
            'slug' => Str::slug($title),
            'status' => $this->faker->randomElement(Hackathon::STATUSES),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
