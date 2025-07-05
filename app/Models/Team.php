<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'hackathon_id', 'title',
    ];

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TeamUser::class)
            ->withPivot(['position_id']);
    }

    public function teamUsers(): HasMany
    {
        return $this->hasMany(TeamUser::class);
    }

    public function project(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public static function getTeams(Hackathon $hackathon): Collection
    {
        $user = auth()->user();

        if ($user->isHackathonStaff($hackathon)) {
            return self::with([
                'teamUsers.user',
                'teamUsers.position',
            ])->where('hackathon_id', $hackathon->id)->get();
        }

        return self::query()->whereHas('users', function ($q) use ($user) {
            $q->where('id', $user->id);
        })->get();

    }
}
