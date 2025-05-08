<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hackathon extends Model
{
    use HasFactory;
    protected $fillable = [
        'title', 'image_path', 'format', 'type', 'min_team_size', 'max_team_size', 'registration_start',
        'registration_end', 'event_start', 'event_end', 'prize_pool', 'slug', 'is_published',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
