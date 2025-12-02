<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizeDistribution extends Model
{
    use Translatable;

    protected $fillable = [
        'nomination_id', 'place', 'prize', 'translations', 'locale',
    ];

    protected array $translatable = ['prize'];

    protected $casts = [
        'translations' => 'array',
    ];

    public function nomination(): BelongsTo
    {
        return $this->belongsTo(Nomination::class);
    }
}
