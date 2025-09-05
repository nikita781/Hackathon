<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const SUPER_ADMIN = 1;
    public const ADMIN = 2;
    public const MODERATOR = 3;
    public const ORGANIZER = 4;
    public const JUDGE = 5;
    public const MENTOR = 6;
    public const MEMBER = 7;

    public const MODERATORS = [self::SUPER_ADMIN, self::ADMIN, self::MODERATOR];
    public const ADMINS = [self::SUPER_ADMIN, self::ADMIN];
    public const STAFF_WITH_ORGANIZER = [self::SUPER_ADMIN, self::ADMIN, self::MODERATOR, self::ORGANIZER, self::JUDGE, self::MENTOR];
    public const STAFF = [self::JUDGE, self::MENTOR];

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
