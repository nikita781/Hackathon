<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabItem extends Model
{
    use HasFactory;

    protected $fillable = ['tab_section_id', 'title', 'content'];

    public function tabSection(): BelongsTo
    {
        return $this->belongsTo(TabSection::class);
    }
}
