<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TabItem extends Model implements HasMedia
{
    use InteractsWithMedia, Translatable;

    protected $fillable = [
        'tab_section_id', 'title', 'content', 'translations', 'locale'
    ];

    public $translatable = ['title', 'content'];

    protected $casts = [
        'translations' => 'array'
    ];

    public function tabSection(): BelongsTo
    {
        return $this->belongsTo(TabSection::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }
}
