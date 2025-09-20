<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EditorUpload extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [];

    public function usedIn(): MorphTo
    {
        return $this->morphTo();
    }
}
