<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'hackathon_id', 'user_id', 'title', 'description', 'preview_path', 'about', 'stack', 'project_link',
        'presentation_path',
        'video_link', 'is_published',
    ];

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectUser::class)
            ->withPivot('position_id');
    }

    public function capitan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fullTeam(): Collection
    {
        $capitan = $this->capitan;
        $members = $this->members;

        return collect([$capitan])->merge($members);
    }
}
