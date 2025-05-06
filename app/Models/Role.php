<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    const SUPER_ADMIN = 1;
    const ADMIN = 2;
    const ORGANIZER = 3;
    const GSK = 4;
    const MEMBER = 5;

    protected $fillable = [
        'role',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
