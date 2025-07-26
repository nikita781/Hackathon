<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Support extends Model
{
    protected $fillable = [
        'user_id', 'hackathon_id', 'type', 'is_completed',
    ];

    const TYPES = ['question', 'suggestion', 'bug', 'other'];

    const QUESTION = 'question';
    const SUGGESTION = 'suggestion';
    const BUG = 'bug';
    const OTHER = 'other';

    public function user(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }
}
