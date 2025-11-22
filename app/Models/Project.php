<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'hackathon_id', 'team_id', 'title', 'description', 'about', 'stack', 'project_link', 'video_link', 'status',
        'moderated_time', 'published_time', 'blocked_time', 'avg_score', 'comment', 'slug',
    ];

    public const PROJECT_STATUS = [self::DRAFT, self::MODERATION, self::PUBLISHED, self::BLOCKED];

    public const DRAFT = 1;
    public const MODERATION = 2;
    public const PUBLISHED = 3;
    public const BLOCKED = 4;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::PUBLISHED);
    }

    /**
     * @return BelongsTo
     */
    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    /**
     * @return BelongsTo
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function updateAvgScore(): void
    {
        $sum = $this->evaluations()->sum('score');
        $this->avg_score = $sum;
        $this->saveQuietly();
    }

    public function scopeFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('projects.title', 'ILIKE', "%{$search}%")
                    ->orWhere('projects.description', 'ILIKE', "%{$search}%");
            });
        });

        $query->when($request->rated, function ($q, $rated) {
            if ($rated === 'yes') {
                $q->whereHas('evaluations');
            } else if ($rated === 'no') {
                $q->whereDoesntHave('evaluations');
            }
        });

        $query->when($request->order, function ($q, $order) {
            return match ($order) {
                'dateA'   => $q->orderBy('projects.moderated_time', 'asc'),
                'dateD'   => $q->orderBy('projects.moderated_time', 'desc'),
                'titleA'  => $q->orderBy('projects.title', 'asc'),
                'titleD'  => $q->orderBy('projects.title', 'desc'),
                'scoreA'  => $q->orderBy('projects.avg_score', 'asc'),
                'scoreD'  => $q->orderBy('projects.avg_score', 'desc'),
                default   => $q,
            };
        });

        return $query;
    }

    public function scopeAdminFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->where('projects.title', 'ILIKE', '%' . $search . '%');
        });

        $query->when($request->status, function ($q, $status) {
            $status = is_array($status) ? $status : explode(',', $status);
            $q->whereIn('projects.status', $status);
        });

        $query->when($request->order, function ($q, $order) {
            return match ($order) {
                'dateA' => $q->orderBy('projects.created_at', 'asc'),
                'dateD' => $q->orderBy('projects.created_at', 'desc'),
                'titleA' => $q->orderBy('projects.title', 'asc'),
                'titleD' => $q->orderBy('projects.title', 'desc'),
                default => $q,
            };
        });

        return $query;
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
