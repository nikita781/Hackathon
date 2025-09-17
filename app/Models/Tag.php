<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'title', 'order', 'slug',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany
     */
    public function hackathons(): BelongsToMany
    {
        return $this->belongsToMany(Hackathon::class);
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        do {
            try {
                DB::beginTransaction();
                if (!self::where('slug', $slug)->exists()) {
                    DB::commit();
                    return $slug;
                }
                DB::rollBack();
            } catch (QueryException $e) {
                DB::rollBack();
            }

            $slug = $original . '-' . $counter;
            $counter++;
        } while (true);
    }
}
