<?php

namespace App\Actions;

use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;

class SyncTeams
{
    public function __invoke(): array
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $stats = [
            'sync' => 0,
            'deleted' => 0,
            'members' => 0,
        ];

        DB::connection(config('team_sync.source', 'main_site'))
            ->table('teams')
            ->select(['id', 'title', 'created_at', 'updated_at', 'deleted_at'])
            ->orderBy('id')
            ->chunk(500, function (Collection $mainTeams) use (&$stats) {
                $this->syncChunk($mainTeams, $stats);
            });

        return $stats;
    }

    private function syncChunk(Collection $mainTeams, array &$stats): void
    {
        $deletedMainSiteTeamIds = $mainTeams
            ->filter(fn ($team) => $team->deleted_at !== null)
            ->pluck('id')
            ->all();

        if (! empty($deletedMainSiteTeamIds)) {
            $this->deleteTeams($deletedMainSiteTeamIds, $stats);
        }

        $activeMainTeams = $mainTeams->filter(fn ($team) => $team->deleted_at === null)->values();
        if ($activeMainTeams->isEmpty()) {
            return;
        }

        $mainTeamIds = $activeMainTeams->pluck('id')->all();

        $teamUsersByMainTeamId = DB::connection(config('team_sync.source', 'main_site'))
            ->table('team_users')
            ->select(['id', 'team_id', 'user_id'])
            ->whereIn('team_id', $mainTeamIds)
            ->orderBy('team_id')
            ->orderBy('id')
            ->get()
            ->groupBy('team_id');

        $allMemberIds = $teamUsersByMainTeamId
            ->flatten(1)
            ->pluck('user_id')
            ->unique()
            ->values();

        $existingUserIds = User::query()
            ->whereIn('id', $allMemberIds)
            ->pluck('id')
            ->all();
        $existingUserLookup = array_fill_keys($existingUserIds, true);

        $records = [];
        $membersByMainTeamId = [];

        foreach ($activeMainTeams as $mainTeam) {
            $mainTeamMemberIds = collect($teamUsersByMainTeamId->get($mainTeam->id, collect()))
                ->pluck('user_id')
                ->unique()
                ->filter(fn ($userId) => isset($existingUserLookup[$userId]))
                ->values();

            $captainId = $mainTeamMemberIds->first();

            $records[] = [
                'main_site_team_id' => $mainTeam->id,
                'sync_source' => Team::SYNC_SOURCE_MAIN_SITE,
                'hackathon_id' => null,
                'owner_id' => $captainId,
                'title' => $mainTeam->title ?: ('Team #'.$mainTeam->id),
                'place' => null,
                'synced_at' => now(),
                'created_at' => $mainTeam->created_at ?? now(),
                'updated_at' => $mainTeam->updated_at ?? now(),
            ];

            $membersByMainTeamId[$mainTeam->id] = $this->buildMembersPayload($mainTeamMemberIds);
        }

        Team::query()->upsert(
            $records,
            ['main_site_team_id'],
            ['sync_source', 'hackathon_id', 'owner_id', 'title', 'place', 'synced_at', 'updated_at']
        );

        $localTeams = Team::query()
            ->where('sync_source', Team::SYNC_SOURCE_MAIN_SITE)
            ->whereIn('main_site_team_id', $mainTeamIds)
            ->get(['id', 'main_site_team_id', 'owner_id']);

        foreach ($localTeams as $localTeam) {
            $membersPayload = $membersByMainTeamId[$localTeam->main_site_team_id] ?? [];
            $localTeam->users()->sync($membersPayload);
            $stats['members'] += count($membersPayload);

            $captainId = array_key_first($membersPayload);
            if ($localTeam->owner_id !== $captainId) {
                $localTeam->owner_id = $captainId;
                $localTeam->save();
            }

            $stats['sync']++;
        }
    }

    private function buildMembersPayload(Collection $memberIds): array
    {
        if ($memberIds->isEmpty()) {
            return [];
        }

        $captainId = $memberIds->first();
        $payload = [];

        foreach ($memberIds as $memberId) {
            $payload[$memberId] = [
                'position_id' => $memberId === $captainId
                    ? Position::CAPITAN_POSITION
                    : Position::UNI_POSITION,
            ];
        }

        return $payload;
    }

    private function deleteTeams(array $deletedMainSiteTeamIds, array &$stats): void
    {
        $teams = Team::query()
            ->where('sync_source', Team::SYNC_SOURCE_MAIN_SITE)
            ->whereIn('main_site_team_id', $deletedMainSiteTeamIds)
            ->get();

        foreach ($teams as $team) {
            $team->delete();
            $stats['deleted']++;
        }
    }
}
