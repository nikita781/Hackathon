<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    protected $fillable = [
        'user_id',
        'hackathon_id',
        'type',
        'is_completed',
        'closed_by',
        'closed_at',
        'is_read',
    ];

    const TYPES = ['question', 'suggestion', 'bug'];

    const QUESTION = 'question';

    const SUGGESTION = 'suggestion';

    const BUG = 'bug';

    protected $casts = [
        'is_completed' => 'boolean',
        'is_read' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function scopeFilter(Builder $query, $request): Builder
    {
        $query->when($request->q, function ($q, $search) {
            $q->whereHas('messages', function ($q2) use ($search) {
                $q2->where('message', 'ILIKE', "%{$search}%");
            });
        });

        $query->when($request->type, function ($q, $type) {
            if (in_array($type, self::TYPES, true)) {
                $q->where('type', $type);
            }
        });

        $query->when(isset($request->is_completed), function ($q) use ($request) {
            $q->where('is_completed', $request->is_completed);
        });

        $query->when($request->order, function ($q, $order) {
            return match ($order) {
                'dateA' => $q->orderBy('created_at', 'asc'),
                'dateD' => $q->orderBy('created_at', 'desc'),
                default => $q,
            };
        });

        return $query;
    }
}
