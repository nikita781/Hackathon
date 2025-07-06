<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MediaCannotBeDeleted;
use Tests\TestCase;

class CreateHackathonTest extends TestCase
{
    use DatabaseTransactions;

    public function test_organizer_can_create_hackathon_with_valid_data(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->roles()->syncWithoutDetaching([Role::ORGANIZER]);

        $this->actingAs($user);

        $tags = Tag::findMany([1, 2, 3]);

        $registrationStart = now()->addDays(1)->format('Y-m-d');
        $registrationEnd = now()->addDays(5)->format('Y-m-d');
        $eventStart = now()->addDays(10)->format('Y-m-d');
        $eventEnd = now()->addDays(15)->format('Y-m-d');

        $response = $this->post(route('hackathons.store'), [
            'title' => 'Test Hackathon',
            'image_path' => UploadedFile::fake()->image('hack.jpg', 800, 600),
            'format' => 'online',
            'type' => 'team',
            'min_team_size' => 2,
            'max_team_size' => 5,
            'registration_start' => $registrationStart,
            'registration_end' => $registrationEnd,
            'event_start' => $eventStart,
            'event_end' => $eventEnd,
            'prize_type' => 'cash',
            'prize_pool' => 10000,
            'is_published' => false,
            'tags' => $tags->pluck('id')->toArray(),
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('hackathons', [
            'title' => 'Test Hackathon',
            'format' => 'online',
            'type' => 'team',
            'min_team_size' => 2,
            'max_team_size' => 5,
            'prize_pool' => 10000,
            'is_published' => false,
        ]);

        $hackathon = Hackathon::where('title', 'Test Hackathon')->firstOrFail();

        $this->assertCount(1, $hackathon->getMedia('main_image'));

        $userMember = User::factory()->create();
        $userMember->roles()->attach(Role::MEMBER);

        $userMember->hackathons()->syncWithoutDetaching([$hackathon->id => ['role_id' => Role::MEMBER]]);

        $this->assertDatabaseHas('hackathon_user', [
            'user_id' => $userMember->id,
            'hackathon_id' => $hackathon->id,
            'role_id' => Role::MEMBER,
        ]);

        $this->assertCount(3, $hackathon->tags);

        $this->assertCount(count(\App\Models\Tab::TAB_TITLES), $hackathon->tabs);
    }
}
