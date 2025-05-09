<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Hackathon extends Model
{
    use HasFactory;
    protected $fillable = [
        'title', 'image_path', 'format', 'type', 'min_team_size', 'max_team_size', 'registration_start',
        'registration_end', 'event_start', 'event_end', 'prize_pool', 'slug', 'is_published',
    ];

    /**
     * @return HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @param  Builder  $query
     * @param $request
     * @return Builder
     */
    public function scopeFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->where('title', 'like', '%' . $search . '%');
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
                            'upcoming' => $q2->where('event_start', '>', now()),
                            'ongoing' => $q2->where('event_start', '<=', now())->where('event_end', '>=', now()),
                            'completed' => $q2->where('event_end', '<', now()),
                            default => null,
                        };
                    });
                }
            });
        });

        $query->when($request->tag, function ($q, $tag) {
            $tags = is_array($tag) ? $tag : explode(',', $tag);
            foreach ($tags as $tagName) {
                $q->whereHas('tags', function ($q2) use ($tagName) {
                    $q2->where('tags.title', $tagName);
                });
            }
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
}
