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
        'name', 'email', 'email_verified_at',
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

    /**
     * @param  int  $role_id
     * @return void
     */
    public function assignedRole(int $role_id): void
    {
        $this->roles()->syncWithoutDetaching([$role_id]);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('position_id')
            ->withTimestamps();
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'team_user')
            ->withPivot('team_id')
            ->withTimestamps();
    }
}
