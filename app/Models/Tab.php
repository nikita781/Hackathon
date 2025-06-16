<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tab extends Model
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'hackathon_id', 'title', 'content',
    ];
    const TAB_TITLES = ['Обзор', 'Ресурсы', 'Правила', 'Контакты', 'Оценка'];

    /**
     * @return BelongsTo
     */
    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TabSection::class);
    }
}
