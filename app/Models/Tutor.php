<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tutor extends Model
{
    use HasFactory;

    /**
     * Get the User account associated with the Tutor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the videos owned/uploaded by the Tutor.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get the Centers the Tutor is affiliated with.
     */
    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_tutor');
    }
}