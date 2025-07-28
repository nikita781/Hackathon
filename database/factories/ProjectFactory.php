<?php

namespace Database\Factories;

use App\Models\Hackathon;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'description' => $this->faker->text(),
//            'preview_path' => 'test/image.jpg',
            'about' => $this->faker->word(),
            'stack' => $this->faker->word(),
            'project_link' => $this->faker->word(),
//            'presentation_path' => 'test/file.pdf',
            'video_link' => $this->faker->word(),
            'status' => $this->faker->randomElement(Project::PROJECT_STATUS),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
