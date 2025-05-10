<?php

namespace Database\Factories;

use App\Models\Tab;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TabFactory extends Factory
{
    protected $model = Tab::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement(['Обзор', 'Ресурсы', 'Правила', 'Контакты']),
            'content' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
