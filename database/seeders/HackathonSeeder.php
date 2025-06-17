<?php

namespace Database\Seeders;

use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Tab;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class HackathonSeeder extends Seeder
{
    public function run(): void
    {
        Hackathon::factory()->count(60)->create()->each(function (Hackathon $hackathon) {
            $imagePath = 'storage/app/public/test/image.jpg';
            if (file_exists($imagePath)) {
                $hackathon
                    ->addMedia($imagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('main_image');
            }
        });
        $hackathons = Hackathon::all();
        $user = User::find(2);
        foreach ($hackathons as $h) {
            for ($i = 0; $i < random_int(1, 5); $i++) {
                $h->tags()->syncWithoutDetaching(Tag::inRandomOrder()->first()->id);
            }
            $h->users()->syncWithoutDetaching([$user->id => ['role_id' => Role::MEMBER]]);
            foreach (Tab::TAB_TITLES as $title) {
                Tab::factory()->create(['title' => $title, 'hackathon_id' => $h->id]);
            }
        }
//        $projects = Project::with('hackathon', 'members')->get();
//
//        foreach ($projects as $p) {
//            for ($i = 0; $i < rand(1, 5); $i++) {
//                $user = User::with('roles')->inRandomOrder()->first();
//                $p->members()->attach($user->id, ['position_id' => Position::inRandomOrder()->first()->id]);
//                $p->hackathon->users()->syncWithoutDetaching([
//                    $user->id => ['role_id' => Role::MEMBER],
//                ]);
//                $p->images()->create([
//                    'path' => 'test/image.png',
//                    'title' => 'test',
//                    'mime' => 'image/png',
//                    'order' => fake()->randomNumber(1),
//                ]);
//            }
//        }
    }
}
