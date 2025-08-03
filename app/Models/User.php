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
        'oauth_id', 'name', 'nickname', 'email', 'date_of_birth', 'phone_number', 'tshort_size',
        'favorite_programming_lang',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'password' => 'hashed',
    ];

    public function getRouteKeyName(): string
    {
        return 'nickname';
    }

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

    public function onHackathonAsMember(Hackathon $hackathon): bool
    {
        return $this->hackathons()->where('hackathons.id', $hackathon->id)->where('role_id', Role::MEMBER)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(Role::ADMINS)->exists();
    }

    public function isHackathonStaff(Hackathon $hackathon): bool
    {
        if ($this->hackathonsAsOrganizer()->where('id', $hackathon->id)->exists()) {
            return true;
        }

        if ($this->hackathons()->where('hackathon_id', $hackathon->id)->whereIn('role_id', Role::STAFF)->exists()) {
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

    public function isCapitan(Project $project): bool
    {
        return $this->teams()->wherePivot('position_id', Position::CAPITAN_POSITION)->where('id', $project->team_id)->exists();
    }

    public function isMemberOfProject(Project $project): bool
    {
        return $this->teams()->where('id', $project->team_id)->exists();
    }

    public function isCapitanOfHackathon(Hackathon $hackathon): bool
    {
        return $this->teams()
            ->where('hackathon_id', $hackathon->id)
            ->wherePivot('position_id', Position::CAPITAN_POSITION)
            ->exists();
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

    public function support(): HasMany
    {
        return $this->hasMany(Support::class);
    }
}
