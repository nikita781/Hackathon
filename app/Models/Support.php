<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Support extends Model
{
    protected $fillable = [
        'user_id', 'hackathon_id', 'type', 'is_completed', 'closed_by', 'closed_at',
    ];

    const TYPES = ['question', 'suggestion', 'bug', 'other'];

    const QUESTION = 'question';
    const SUGGESTION = 'suggestion';
    const BUG = 'bug';
    const OTHER = 'other';

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
}
