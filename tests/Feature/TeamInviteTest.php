<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TeamInviteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function team(): void
    {
        $org = User::factory()->create(['id' => 3]);
        $org->assignedRole(Role::ORGANIZER);
        $user = User::factory()->create();
        $user->assignedRole(Role::MEMBER);
        $hackathon = Hackathon::factory()->create(['is_published' => true, 'max_team_size' => 2]);
        $this->actingAs($user);

        $response = $this->postJson(route('hackathons.join', $hackathon));

        $team = $user->teams()->firstOrFail();

        $response = $this->postJson(route('hackathons.teams.create-invite', [$hackathon, $team]));
        $response1 = $this->postJson(route('hackathons.teams.create-invite', [$hackathon, $team]));
        $response2 = $this->postJson(route('hackathons.teams.create-invite', [$hackathon, $team]));
        $response3 = $this->postJson(route('hackathons.teams.create-invite', [$hackathon, $team]));


        $invite = TeamInvite::where('token', basename($response->json('url')))->firstOrFail();
        $invite1 = TeamInvite::where('token', basename($response1->json('url')))->firstOrFail();
        $invite2 = TeamInvite::where('token', basename($response2->json('url')))->firstOrFail();
        $invite3 = TeamInvite::where('token', basename($response3->json('url')))->firstOrFail();

        $response->assertOk()
            ->assertJsonStructure(['url', 'expires_at']);

        $this->assertDatabaseHas('team_invites', ['team_id' => $team->id, 'token' => $invite->token]);
        $this->assertDatabaseHas('team_invites', ['team_id' => $team->id, 'token' => $invite1->token]);
        $this->assertDatabaseHas('team_invites', ['team_id' => $team->id, 'token' => $invite2->token]);
        $this->assertDatabaseHas('team_invites', ['team_id' => $team->id, 'token' => $invite3->token]);

        $invite2->update(['expires_at' => Carbon::now()->subHour()]);

//        $response = $this->get(route('hackathons.teams.invite.show', [$hackathon, $team, $response->json('url')]));
//
//        $response->assertOk();

//        -----------------------------
        $user = User::factory()->create();
        $user->assignedRole(Role::MEMBER);
        $this->actingAs($user);

        $response = $this->post(route('hackathons.teams.accept-invite', [$hackathon, $team, $invite->token]));

        $response->assertRedirect();
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('team_invites', ['id' => $invite->id]);
//        -----------------------------

        $user = User::factory()->create();
        $user->assignedRole(Role::MEMBER);
        $this->actingAs($user);

        $response = $this->post(route('hackathons.teams.accept-invite', [$hackathon, $team, $invite1->token]));

        $response->assertBadRequest();
        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('team_invites', ['id' => $invite1->id]);
//        -----------------------------

        $user = User::factory()->create();
        $user->assignedRole(Role::MEMBER);
        $this->actingAs($user);

        $response = $this->post(route('hackathons.teams.accept-invite', [$hackathon, $team, $invite2->token]));

        $response->assertStatus(410);
        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('team_invites', ['id' => $invite2->id]);
//        -----------------------------

        $user = User::factory()->create();
        $user->assignedRole(Role::MEMBER);
        $user->hackathons()->attach($hackathon->id, ['role_id' => Role::JUDGE]);
        $this->actingAs($user);

        $response = $this->post(route('hackathons.teams.accept-invite', [$hackathon, $team, $invite3->token]));

        $response->assertStatus(404);
        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('team_invites', ['id' => $invite3->id]);
    }
}
