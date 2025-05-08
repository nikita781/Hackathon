<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hackathon extends Model
{
    protected $fillable = [
        'title', 'image_path', 'format', 'type', 'min_team_size', 'max_team_size', 'registration_start',
        'registration_end', 'event_start', 'event_end', 'prize_pool', 'slug', 'is_published',
    ];
}
