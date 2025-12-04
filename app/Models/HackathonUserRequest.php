<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HackathonUserRequest extends Model
{
    protected $fillable = [
        'hackathon_id', 'user_id', 'status',
    ];

    public const STATUS_PENDING = 1;
    public const STATUS_ACCEPT = 2;
    public const STATUS_REJECT = 3;

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
