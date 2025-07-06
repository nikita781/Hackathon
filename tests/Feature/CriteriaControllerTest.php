<?php

namespace Tests\Feature;

use App\Models\CriterionGroup;
use App\Models\Hackathon;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CriteriaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_criteria_crud_flow()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create(['id' => 3]);
        $user->assignedRole(Role::ORGANIZER);

        $hackathon = Hackathon::factory()->create();

        $this->actingAs($user);

        $createPayload = [
            'title' => 'Тестовая группа критериев',
            'criteria' => [
                ['title' => 'Креативность', 'max_score' => 10],
                ['title' => 'Реализация', 'max_score' => null],
            ],
        ];

        $response = $this->post(route('hackathons.criteria.store', $hackathon), $createPayload);
        $response->assertRedirect();

        $this->assertDatabaseHas('criterion_groups', [
            'title' => 'Тестовая группа критериев',
            'hackathon_id' => $hackathon->id,
        ]);

        $group = CriterionGroup::where('hackathon_id', $hackathon->id)->first();
        $this->assertEquals(2, $group->criteria()->count());

        $this->assertDatabaseHas('criteria', [
            'title' => 'Реализация',
            'max_score' => 10,
            'criterion_group_id' => $group->id,
        ]);

        $updatePayload = [
            'title' => 'Обновленная группа критериев',
            'criteria' => [
                ['title' => 'UI/UX'],
            ],
        ];

        $response = $this->patch(route('hackathons.criteria.update', [$hackathon, $group]), $updatePayload);
        $response->assertRedirect();

        $this->assertDatabaseMissing('criterion_groups', [
            'id' => $group->id,
        ]);

        $this->assertDatabaseHas('criterion_groups', [
            'title' => 'Обновленная группа критериев',
            'hackathon_id' => $hackathon->id,
        ]);

        $newGroup = CriterionGroup::where('title', 'Обновленная группа критериев')->first();
        $this->assertNotNull($newGroup);
        $this->assertEquals(1, $newGroup->criteria()->count());

        $response = $this->delete(route('hackathons.criteria.destroy', [$hackathon, $newGroup]));
        $response->assertRedirect();

        $this->assertDatabaseMissing('criterion_groups', ['id' => $newGroup->id]);
        $this->assertDatabaseMissing('criteria', ['criterion_group_id' => $newGroup->id]);
    }
}
