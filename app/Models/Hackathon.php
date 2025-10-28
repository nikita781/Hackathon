<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Hackathon extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'title',
        'format',
        'type',
        'min_team_size',
        'max_team_size',
        'registration_start',
        'registration_end',
        'event_start',
        'event_end',
        'prize_type',
        'prize_pool',
        'work_time_start',
        'work_time_end',
        'evaluation_start',
        'evaluation_end',
        'slug',
        'status',
        'moderated_time',
        'published_time',
        'blocked_time',
        'comment',
        'is_finished',
    ];

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_MODERATION, self::STATUS_PUBLISHED, self::STATUS_BLOCKED];
    public const STATUS_DRAFT = 1;
    public const STATUS_MODERATION = 2;
    public const STATUS_PUBLISHED = 3;
    public const STATUS_BLOCKED = 4;

    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'event_start' => 'datetime',
        'event_end' => 'datetime',
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'evaluation_start' => 'datetime',
        'evaluation_end' => 'datetime',
        'work_time_start' => 'datetime',
        'work_time_end' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function ownTeam(User $user)
    {
        return $user->teams()
            ->whereHas('hackathon', fn($q) => $q->where('id', $this->id))
            ->with(['projects', 'teamUsers.user', 'teamUsers.position'])
            ->first();
    }

    public function allProjects(): HasManyThrough
    {
        return $this->hasManyThrough(Project::class, Team::class);
    }

    public function scopeWithProjectCounts(Builder $query): Builder
    {
        return $query->withCount([
            'allProjects',
            'allProjects as moderation_projects_count' => fn($q) => $q->where('status', Project::MODERATION),
            'allProjects as accepted_projects_count'   => fn($q) => $q->where('status', Project::PUBLISHED),
            'allProjects as rejected_projects_count'   => fn($q) => $q->where('status', Project::BLOCKED),
        ]);
    }

    public function scopeWithUserCounts(Builder $query): Builder
    {
        return $query->withCount([
            'users',
        ]);
    }

    public function allTeams(): HasMany
    {
        return $this->teams();
    }

    public function countTeams(Request $request): int
    {
        return $this->teams()->filter($request)->count();
    }

    public function getAllHackathonStaff(): Collection
    {
        return $this->users()
            ->with('roles')
            ->withPivot('role_id')
            ->wherePivotIn('role_id', Role::STAFF)
            ->get();
    }

    /**
     * @return HasMany
     */
    public function tabs(): HasMany
    {
        return $this->hasMany(Tab::class);
    }

    /**
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_id');
    }

    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('role_id', Role::MEMBER);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class);
    }

    public function criteriaGroups(): HasMany
    {
        return $this->hasMany(CriterionGroup::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }

    public function support(): HasMany
    {
        return $this->hasMany(Support::class);
    }

    public function assignPlaces(): void
    {
        $projects = $this->allProjects()
            ->published()
            ->with('team')
            ->orderByDesc('avg_score')
            ->get();

        DB::transaction(function () use ($projects) {
            $place = 1;

            foreach ($projects as $project) {
                $project->team->update(['place' => $place]);
                $place++;
            }
        });
    }

    public function getMemberPlace(User $member): int|null
    {
        $team = $this->teams()
            ->whereHas('teamUsers', fn($q) => $q->where('user_id', $member->id))
            ->first();

        return $team?->place;
    }

    /**
     * @param  Builder  $query
     * @param $request
     * @return Builder
     */
    public function scopeFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->where('title', 'ILIKE', '%' . $search . '%');
        });

        $query->when($request->format, function ($q, $format) {
            $formats = is_array($format) ? $format : explode(',', $format);
            $q->whereIn('format', $formats);
        });

        $query->when($request->type, function ($q, $type) {
            $types = is_array($type) ? $type : explode(',', $type);
            $q->whereIn('type', $types);
        });

        $query->when($request->status, function ($q, $status) {
            $statuses = is_array($status) ? $status : explode(',', $status);

            $q->where(function ($query) use ($statuses) {
                foreach ($statuses as $s) {
                    $query->orWhere(function ($q2) use ($s) {
                        match ($s) {
                            'upcoming' => $q2->whereDate('event_start', '>', now()),
                            'ongoing' => $q2->whereDate('event_start', '<=', now())->whereDate('event_end', '>=', now()),
                            'completed' => $q2->whereDate('event_end', '<', now()),
                            default => null,
                        };
                    });
                }
            });
        });

        $query->when($request->tags, function ($q, $tag) {
            $tags = is_array($tag) ? $tag : explode(',', $tag);
            $q->whereHas('tags', function ($q2) use ($tags) {
                $q2->whereIn('tags.slug', $tags);
            });
        });

        $query->when($request->order, function ($q, $order) {
            return match ($order) {
                'dateA' => $q->orderBy('event_start', 'asc'),
                'dateD' => $q->orderBy('event_start', 'desc'),
                'titleA' => $q->orderBy('title', 'asc'),
                'titleD' => $q->orderBy('title', 'desc'),
                default => $q,
            };
        });

        return $query;
    }

    /**
     * @param  Builder  $query
     * @param $request
     * @return Builder
     */
    public function scopeAdminFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->where('title', 'ILIKE', '%' . $search . '%');
        });

        $query->when($request->status, function ($q, $status) {
            $status = is_array($status) ? $status : explode(',', $status);
            $q->whereIn('status', $status);
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

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        do {
            try {
                DB::beginTransaction();
                if (!self::where('slug', $slug)->exists()) {
                    DB::commit();
                    return $slug;
                }
                DB::rollBack();
            } catch (QueryException $e) {
                DB::rollBack();
            }

            $slug = $original . '-' . $counter;
            $counter++;
        } while (true);
    }
}
