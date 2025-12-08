<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;
    
    /**
     * Get the category that the course belongs to.
     */
    protected $fillable = [
    'category_id',
    'uploader_user_id',
    'assigned_tutor_id',
    'title',
    'publish',
    'slug',
    'description',
    'image_thumbnail_url',
    'type',
];
protected static function boot()
    {
        parent::boot();
        static::creating(fn($c) => $c->slug = Str::slug($c->title));
        static::updating(function ($c) {
            if ($c->isDirty('title')) $c->slug = Str::slug($c->title);
        });
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

public function getRouteKeyName()
{
    return 'slug'; // ← THIS IS THE MAGIC LINE
}
public function resolveRouteBinding($value, $field = null)
{
    return $this->where($field ?? 'id', $value)
                ->orWhere('slug', $value)
                ->firstOrFail();
}
public function videos()
{
    return $this->belongsToMany(Video::class, 'course_video')
                ->withPivot('order_index') // Retrieve the sequence number
                ->orderBy('pivot_order_index'); // Order lessons by sequence
}

    /**
     * Get the price history for the course.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

  public function price(): HasOne
    {
        return $this->hasOne(Price::class);
    }

    public function users(): BelongsToMany
    {
        // Explicitly defining the pivot table name for clarity
        return $this->belongsToMany(User::class, 'course_user', 'course_id', 'user_id');
    }
public function assignedTutor(): BelongsTo
{
    // Links to the 'tutors' table using the 'assigned_tutor_id' foreign key
    return $this->belongsTo(Tutor::class, 'assigned_tutor_id');
}

/**
 * Get the User (staff/admin) who uploaded/created the course record.
 */
public function uploader(): BelongsTo
{
    // Links to the 'users' table using the 'uploader_user_id' foreign key
    return $this->belongsTo(User::class, 'uploader_user_id');
}
    /**
     * Get the Centers that physically offer this course.
     */
    public function centers(): BelongsToMany
    {
        // Must specify the pivot table and include extra pivot columns
        return $this->belongsToMany(Center ::class, 'center_course')
                    ->withPivot(['price', 'start_date', 'end_date'])
                    ->withTimestamps();
    }

    /**
     * Get the current price of the course.
     */
    public function currentPrice(): HasOne
    {
        // Requires Price model to be defined
        return $this->hasOne(Price::class)->where('is_current', true);
    }
    
    // --- Polymorphic Relations ---

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
    
    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }


       public function getUpvotesAttribute()
    {
        return $this->likes()->where('type', 'up')->count();
    }

    // total downvotes
    public function getDownvotesAttribute()
    {
        return $this->likes()->where('type', 'down')->count();
    }

    
}