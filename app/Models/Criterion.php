<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    use Translatable;

    protected $fillable = [
        'criterion_group_id', 'title', 'max_score', 'translations', 'locale'
    ];

    protected array $translatable = ['title'];

    protected $casts = [
        'translations' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CriterionGroup::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
