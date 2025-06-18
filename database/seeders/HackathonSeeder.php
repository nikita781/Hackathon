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
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class HackathonSeeder extends Seeder
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function run(): void
    {
        $imagePath = 'storage/app/public/test/image.jpg';
        Hackathon::factory()->count(60)->create()->each(function (Hackathon $hackathon) use ($imagePath) {
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
            foreach (Tab::defaultStructure() as $tabTitle => $sections) {
                $tab = Tab::factory()->create(['title' => $tabTitle, 'hackathon_id' => $h->id]);
                if (file_exists($imagePath)) {
                    for ($i = 0; $i < random_int(0,2); $i++) {
                        $tab
                            ->addMedia($imagePath)
                            ->preservingOriginal()
                            ->toMediaCollection('files');
                    }

                    for ($i = 0; $i < random_int(0,2); $i++) {
                        $tab
                            ->addMedia($imagePath)
                            ->preservingOriginal()
                            ->toMediaCollection('partner_images');
                    }
                }
                foreach ($sections as $sectionTitle) {
                    $tab->sections()->create(['title' => $sectionTitle]);
                }
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
