<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_profile_team_as_captain(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('profile.teams.store'), [
                'title' => 'Backend Squad',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('team.title', 'Backend Squad')
            ->assertJsonPath('team.owner.id', $user->id)
            ->assertJsonPath('team.is_profile_team', true);

        $teamId = $response->json('team.id');

        $this->assertDatabaseHas('teams', [
            'id' => $teamId,
            'owner_id' => $user->id,
            'hackathon_id' => null,
            'title' => 'Backend Squad',
        ]);

        $this->assertDatabaseHas('team_user', [
            'team_id' => $teamId,
            'user_id' => $user->id,
            'position_id' => Position::CAPITAN_POSITION,
        ]);
    }

    /** @test */
    public function profile_team_index_separates_created_and_member_teams(): void
    {
        $owner = User::factory()->create(['password' => 'password']);
        $member = User::factory()->create(['password' => 'password']);
        $hackathon = Hackathon::factory()->create(['user_id' => $owner->id]);

        $createdTeam = Team::create([
            'owner_id' => $member->id,
            'title' => 'Created Team',
        ]);
        $createdTeam->users()->attach($member->id, [
            'position_id' => Position::CAPITAN_POSITION,
        ]);

        $joinedTeam = Team::create([
            'owner_id' => $owner->id,
            'title' => 'Joined Team',
        ]);
        $joinedTeam->users()->attach($owner->id, [
            'position_id' => Position::CAPITAN_POSITION,
        ]);
        $joinedTeam->users()->attach($member->id, [
            'position_id' => Position::UNI_POSITION,
        ]);

        $hackathonTeam = Team::create([
            'hackathon_id' => $hackathon->id,
            'title' => 'Hackathon Team',
        ]);
        $hackathonTeam->users()->attach($member->id, [
            'position_id' => Position::CAPITAN_POSITION,
        ]);

        $response = $this
            ->actingAs($member)
            ->getJson(route('profile.teams.index'));

        $response
            ->assertOk()
            ->assertJsonPath('createdTeams.0.id', $createdTeam->id)
            ->assertJsonPath('memberTeams.0.id', $joinedTeam->id);

        $createdIds = collect($response->json('createdTeams'))->pluck('id');
        $memberIds = collect($response->json('memberTeams'))->pluck('id');

        $this->assertFalse($createdIds->contains($hackathonTeam->id));
        $this->assertFalse($memberIds->contains($hackathonTeam->id));
    }

    /** @test */
    public function captain_can_invite_user_to_profile_team(): void
    {
        $captain = User::factory()->create(['password' => 'password']);
        $member = User::factory()->create(['password' => 'password']);

        $team = Team::create([
            'owner_id' => $captain->id,
            'title' => 'Invite Team',
        ]);
        $team->users()->attach($captain->id, [
            'position_id' => Position::CAPITAN_POSITION,
        ]);

        $inviteResponse = $this
            ->actingAs($captain)
            ->postJson(route('profile.teams.create-invite', $team));

        $inviteResponse
            ->assertOk()
            ->assertJsonStructure(['url', 'expires_at']);

        $token = basename($inviteResponse->json('url'));

        $this
            ->actingAs($member)
            ->getJson(route('profile.teams.accept-invite', [$team, $token]))
            ->assertOk()
            ->assertJsonPath('status', __('joined_team'));

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'position_id' => Position::UNI_POSITION,
        ]);
    }

    /** @test */
    public function blocked_user_cannot_accept_profile_team_invite(): void
    {
        $captain = User::factory()->create(['password' => 'password']);
        $blockedUser = User::factory()->create([
            'password' => 'password',
            'status' => User::STATUS_BLOCKED,
        ]);

        $team = Team::create([
            'owner_id' => $captain->id,
            'title' => 'Blocked Invite Team',
        ]);
        $team->users()->attach($captain->id, [
            'position_id' => Position::CAPITAN_POSITION,
        ]);

        $inviteResponse = $this
            ->actingAs($captain)
            ->postJson(route('profile.teams.create-invite', $team));

        $token = basename($inviteResponse->json('url'));

        $this
            ->actingAs($blockedUser)
            ->getJson(route('profile.teams.accept-invite', [$team, $token]))
            ->assertForbidden();

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $blockedUser->id,
        ]);
    }
}
