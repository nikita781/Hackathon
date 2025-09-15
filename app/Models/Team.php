<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'hackathon_id', 'title', 'place'
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

    public function captain(): mixed
    {
        return $this->users()->wherePivot('position_id', Position::CAPITAN_POSITION)->first();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->where('title', 'ILIKE', '%' . $search . '%');
        });

        $query->when($request->team, function ($q, $team) {
            if ($team === 'yes') {
                $q->has('teamUsers', '>=', 2);
            } elseif ($team === 'no') {
                $q->has('teamUsers', '=', 1);
            }
        });

        $query->when($request->status, function ($q, $status) {
            $q->when($status, function ($q, $status) {
                $q->whereHas('projects', function ($query) use ($status) {
                    if ($status == Project::PUBLISHED) {
                        $query->where('status', Project::PUBLISHED);
                    } else {
                        $query->whereIn('status', [Project::DRAFT, Project::MODERATION, Project::BLOCKED]);
                    }
                });
            });
        });

        $query->when($request->order, function ($q, $order) {
            return match ($order) {
                'dateA' => $q->orderBy('created_at', 'asc'),
                'dateD' => $q->orderBy('created_at', 'desc'),
                'titleA' => $q->orderBy('title', 'asc'),
                'titleD' => $q->orderBy('title', 'desc'),
                default => $q,
            };
        });

        return $query;
    }
}
