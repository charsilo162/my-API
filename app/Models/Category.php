<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Get the courses for the category.
     */
      protected $fillable = ['name', 'slug', 'thumbnail_url'];

    protected static function boot()
    {
        parent::boot();

        static::creating(fn($c) => $c->slug = \Str::slug($c->name));
        static::updating(function ($c) {
            if ($c->isDirty('name')) $c->slug = \Str::slug($c->name);
        });
    }
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}