<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nomination extends Model
{
    use Translatable;

    protected $fillable = [
        'hackathon_id', 'title', 'prize', 'translations', 'locale',
    ];

    protected array $translatable = ['title', 'prize'];

    protected $casts = [
        'translations' => 'array',
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
