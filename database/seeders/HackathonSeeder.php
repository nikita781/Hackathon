<?php

namespace Database\Seeders;

use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class HackathonSeeder extends Seeder
{
    public function run(): void
    {
        Hackathon::factory()->count(3)->create();
        $hackathons = Hackathon::all();

        foreach ($hackathons as $h) {
            $h->projects()->saveMany(Project::factory()->count(3)->make());
        }
        $projects = Project::all();

        foreach ($projects as $p) {
            for ($i = 0; $i < rand(1, 5); $i++) {
                $p->members()->attach(User::inRandomOrder()->first()->id, ['position_id' => Position::inRandomOrder()->first()->id]);
            }
        }
    }
}
