<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TabSection extends Model
{
    protected $fillable = ['tab_id', 'title', 'content'];

    public function tab(): BelongsTo
    {
        return $this->belongsTo(Tab::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TabItem::class)->orderBy('id');
    }

    public function editorUploads(): MorphMany
    {
        return $this->morphMany(EditorUpload::class, 'used_in');
    }
}
