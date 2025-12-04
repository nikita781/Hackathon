<?php

namespace App\Policies;

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class HackathonPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return $hackathon->status !== Hackathon::STATUS_DRAFT;
        }

        return true;
    }

    public function viewTask(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return false;
        }

        $isHackathonPublish = $hackathon->status === Hackathon::STATUS_PUBLISHED && $hackathon->event_start->lessThan(now());

        return $isHackathonPublish || $user->isHackathonStaff($hackathon) || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->hasRole(Role::ORGANIZER);
    }

    public function update(User $user, Hackathon $hackathon): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($hackathon->is_finished === true) {
            return false;
        }

        return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function updatePublished(User $user, Hackathon $hackathon): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($hackathon->status === Hackathon::STATUS_PUBLISHED || $hackathon->status === Hackathon::STATUS_MODERATION) {
            return false;
        }

        return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function publish(User $user, Hackathon $hackathon): bool
    {
        if (!($hackathon->status === Hackathon::STATUS_DRAFT) && !($hackathon->status === Hackathon::STATUS_BLOCKED)) {
            return false;
        }

        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function delete(User $user, Hackathon $hackathon): bool
    {
        if ($hackathon->status === Hackathon::STATUS_PUBLISHED) {
            return false;
        }

        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }

    public function join(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($hackathon->registration_end < now()) {
            return false;
        }

        if ($hackathon->isPrivate()) {
            return false;
        }

        if ($user->isHackathonStaff($hackathon)) {
            return false;
        }

        if ($user->isAdmin()) {
            return false;
        }

        if ($user->hackathons()->where('hackathon_id', $hackathon->id)->exists()) {
            return false;
        }

        if ($user->hasRole(Role::MEMBER)) {
            return $hackathon->status === Hackathon::STATUS_PUBLISHED;
        }

        return false;
    }

    public function acceptInvite(?User $user, Hackathon $hackathon): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($user->isHackathonStaff($hackathon)) {
            return false;
        }

        if ($user->hasRole(Role::MEMBER)) {
            return $hackathon->status === Hackathon::STATUS_PUBLISHED;
        }

        return false;
    }

    public function leave(User $user, Hackathon $hackathon): bool
    {
        if ($user->isHackathonStaff($hackathon)) {
            return false;
        }

        if (!$user->hackathons()->where('hackathon_id', $hackathon->id)->exists()) {
            return false;
        }

        if ($hackathon->is_finished) {
            return false;
        }

        $team = $user
            ->teams()
            ->where('hackathon_id', $hackathon->id)
            ->first();

        if ($team && $team->users()->count() > 1) {
            return false;
        }

        if ($user->hasRole(Role::MEMBER)) {
            return $hackathon->status === Hackathon::STATUS_PUBLISHED;
        }

        return false;
    }

    public function evaluation(User $user, Hackathon $hackathon): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        if ($hackathon->evaluation_start > now() || $hackathon->evaluation_end < now()) {
            return false;
        }

        if ($hackathon->owner->id === $user->id) {
            return true;
        }

        if ($user->hasRole(Role::ADMIN)) {
            return true;
        }

        return $user
            ->hackathons()
            ->where('hackathons.id', $hackathon->id)
            ->wherePivot('role_id', Role::JUDGE)
            ->exists();
    }

    public function moderate(User $user): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->isAdmin();
    }

    public function approve(User $user, Hackathon $hackathon): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($hackathon->owner->id === $user->id) {
            return true;
        }

        return false;
    }

    public function admin(User $user): bool
    {
        if ($user->status === User::STATUS_BLOCKED) {
            return false;
        }

        return $user->isTopAdmin();
    }

    public function downloadCertificate(User $user, Hackathon $hackathon): bool
    {
        return $user->teams()->where('hackathon_id', $hackathon->id)?->first()?->place <= 3;
    }

    public function downloadReport(User $user, Hackathon $hackathon): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hackathons()->where('hackathon_id', $hackathon->id)->wherePivot('role_id', Role::JUDGE)->exists();
    }

    public function finish(User $user, Hackathon $hackathon)
    {
        return $hackathon->event_end < Carbon::now()
            && $hackathon->status === Hackathon::STATUS_PUBLISHED
            && $hackathon->is_finished === false
            && $user->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists();
    }
}
