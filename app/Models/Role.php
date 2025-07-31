<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const SUPER_ADMIN = 1;
    public const ADMIN = 2;
    public const ORGANIZER = 3;
    public const JUDGE = 4;
    public const MENTOR = 5;
    public const MEMBER = 6;

    public const STAFF_WITH_ORGANIZER = [self::SUPER_ADMIN, self::ADMIN, self::ORGANIZER, self::JUDGE, self::MENTOR];
    public const STAFF = [self::SUPER_ADMIN, self::ADMIN, self::JUDGE, self::MENTOR];

    protected $fillable = [
        'title',
    ];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
