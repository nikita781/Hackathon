<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Position extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'title',
    ];

    public const CAPITAN_POSITION = 1;
    public const UNI_POSITION = 2;

    public static function getAllPositionExceptCapitan(): Collection
    {
        return self::where('id', '>', self::CAPITAN_POSITION)->get();
    }
}
