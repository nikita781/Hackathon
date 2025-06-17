<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TabSection extends Model
{
    use HasFactory;

    protected $fillable = ['tab_id', 'title', 'content'];

    public function tab(): BelongsTo
    {
        return $this->belongsTo(Tab::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TabItem::class)->orderBy('id');
    }
}
