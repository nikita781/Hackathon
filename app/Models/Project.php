<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'hackathon_id', 'user_id', 'title', 'description', 'preview_path', 'about', 'stack', 'project_link',
        'presentation_path',
        'video_link', 'is_published',
    ];

    /**
     * @return BelongsTo
     */
    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    /**
     * @return BelongsToMany
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectUser::class)
            ->withPivot('position_id');
    }

    /**
     * @return BelongsTo
     */
    public function capitan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return Collection
     */
    public function fullTeam(): Collection
    {
        $capitan = $this->capitan;
        $members = $this->members;

        return collect([$capitan])->merge($members);
    }

    /**
     * @return MorphMany
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
