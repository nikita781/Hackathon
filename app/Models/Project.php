<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'hackathon_id', 'team_id', 'title', 'description', 'about', 'stack', 'project_link', 'video_link', 'status',
        'moderated_time', 'published_time', 'blocked_time', 'avg_score',
    ];

    public const DRAFT = 'draft';
    public const MODERATION = 'moderation';
    public const PUBLISHED = 'published';
    public const BLOCKED = 'blocked';

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
        $avg = $this->evaluations()->avg('score');
        $this->avg_score = $avg;
        $this->saveQuietly();
    }

    public function scopeFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->where('title', 'ILIKE', '%' . $search . '%')
                ->orWhere('description', 'ILIKE', '%' . $search . '%');
        });

        $query->when($request->q, function ($q, $status) {
            $q->where('status', $status);
        });

        $query->when($request->order, function ($q, $order) {
            return match ($order) {
                'dateA' => $q->orderBy('moderated_time', 'asc'),
                'dateD' => $q->orderBy('moderated_time', 'desc'),
                'titleA' => $q->orderBy('title', 'asc'),
                'titleD' => $q->orderBy('title', 'desc'),
                'scoreA' => $q->orderBy('avg_score', 'asc'),
                'scoreD' => $q->orderBy('avg_score', 'desc'),
                default => $q,
            };
        });

        return $query;
    }
}
