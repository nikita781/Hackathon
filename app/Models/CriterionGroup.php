<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CriterionGroup extends Model
{
    use Translatable;

    protected $fillable = [
        'hackathon_id', 'title', 'translations', 'locale',
    ];

    protected array $translatable = ['title'];

    protected $casts = [
        'translations' => 'array',
    ];

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function criteria(): hasMany
    {
        return $this->hasMany(Criterion::class);
    }
}
