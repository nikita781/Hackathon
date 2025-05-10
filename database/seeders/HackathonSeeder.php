<?php

namespace Database\Seeders;

use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Tag;
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
            $user = User::whereHas('roles', function ($q) {
                $q->where('role_id', Role::ORGANIZER);
            })->inRandomOrder()->first();
            $h->users()->attach($user->id, ['role_id' => Role::ORGANIZER]);
            for ($i = 0; $i < rand(1, 5); $i++) {
                $h->tags()->attach(Tag::inRandomOrder()->first()->id);
            }
        }
        $projects = Project::with('hackathon', 'members')->get();

        foreach ($projects as $p) {
            for ($i = 0; $i < rand(1, 5); $i++) {
                $user = User::with('roles')->inRandomOrder()->first();
                $p->members()->attach($user->id, ['position_id' => Position::inRandomOrder()->first()->id]);
                $p->hackathon->users()->syncWithoutDetaching([
                    $user->id => ['role_id' => Role::MEMBER],
                ]);
            }
        }
    }
}
