<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeDistribution extends Model
{
    protected $fillable = [
        'nomination_id', 'place', 'prize',
    ];

    public function nomination(): BelongsTo
    {
        return $this->belongsTo(Nomination::class);
    }
}
