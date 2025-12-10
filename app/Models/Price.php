<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Price extends Model
{
    use HasFactory;

    /**
     * Get the Course this price belongs to.
     */

        protected $fillable = [
            'course_id',
            'amount',
            'currency',
            'is_current',
            ];
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}