<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use Translatable;

    protected $fillable = [
        'user_id', 'support_id', 'message', 'message_type', 'translations', 'locale'
    ];

    protected array $translatable = ['message'];

    protected $casts = [
        'translations' => 'array'
    ];

    public const TYPES = ['support', 'user'];
    public const USER = 'user';
    public const SUPPORT = 'support';

    public function user(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function support(): BelongsTo
    {
        return $this->belongsTo(Support::class);
    }
}
