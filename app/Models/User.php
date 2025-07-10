<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'email_verified_at', 'password', 'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @return HasMany
     */
    public function hackathonsAsOrganizer(): HasMany
    {
        return $this->hasMany(Hackathon::class);
    }

    /**
     * @return BelongsToMany
     */
    public function hackathons(): BelongsToMany
    {
        return $this->belongsToMany(Hackathon::class)
            ->withPivot('role_id');
    }

    /**
     * @param  int  $role_id
     * @return bool
     */
    public function hasRole(int $role_id): bool
    {
        return $this->roles->contains('id', $role_id);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function isHackathonStaff(Hackathon $hackathon): bool
    {
        $isAdmin = $this->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::ADMIN,
        ]);

        if ($isAdmin) {
            return true;
        }

        if ($this->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists()) {
            return true;
        }

        if ($this->hackathons()->where('hackathon_id', $hackathon->id)->whereIn('role_id', [Role::JUDGE, Role::MENTOR])->exists()) {
            return true;
        }

        return false;
    }

    /**
     * @param  int  $role_id
     * @return void
     */
    public function assignedRole(int $role_id): void
    {
        $this->roles()->syncWithoutDetaching([$role_id]);
    }

    /**
     * @return BelongsToMany
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(TeamUser::class)
            ->withPivot(['position_id']);
    }

    /**
     * @return HasMany
     */
    public function teamUsers(): HasMany
    {
        return $this->hasMany(TeamUser::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * @return BelongsToMany
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'team_user')
            ->withPivot('team_id')
            ->withTimestamps();
    }
}
