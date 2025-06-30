<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nomination extends Model
{
    protected $fillable = [
        'hackathon_id', 'title', 'prize',
    ];

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function distribution(): HasMany
    {
        return $this->hasMany(PrizeDistribution::class);
    }
}
