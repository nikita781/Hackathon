<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Award extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'hackathon_id', 'title', 'description', 'place', 'for_all', 'system',
    ];

    protected $casts = [
        'for_all' => 'boolean',
        'system' => 'boolean',
    ];

    public const SYSTEM_AWARD_FIRST = 1;
    public const SYSTEM_AWARD_TEN = 2;

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
