<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Collection\Collection;

class Position extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'title',
    ];

    public const CAPITAN_POSITION = 1;
    public const UNI_POSITION = 2;

    public static function getAllPositionExceptCapitan(): array
    {
        return self::where('id', '>', self::CAPITAN_POSITION)->get();
    }
}
