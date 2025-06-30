<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tab extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'hackathon_id', 'title',
    ];
    public const TAB_TITLES = ['Обзор', 'Ресурсы', 'Правила', 'Контакты', 'Оценка', 'Награды'];

    public static function defaultStructure(): array
    {
        return [
            'Обзор' => [
                'Описание',
                'План проведения',
            ],
            'Ресурсы' => [
                'Ресурсы',
            ],
            'Правила' => [
                'Правила',
            ],
            'Контакты' => [
                'Контакт',
                'Ссылки на социальные сети',
            ],
            'Оценка' => [
                'Критерии оценки',
            ],
            'Награды' => [
                'Награды для участников',
            ],
        ];
    }

    /**
     * @return BelongsTo
     */
    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TabSection::class)->orderBy('id');
    }
}
