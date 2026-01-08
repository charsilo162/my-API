<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory,HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'type',
        'photo_path',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * A User can be a Tutor.
     */
    public function tutor(): HasOne
    {
        return $this->hasOne(Tutor::class);
    }

    /**
     * Get all likes made by the User.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }
    
    /**
     * Get all comments made by the User.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * Get all shares made by the User.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

        public function courses()
    {
        // Assuming a many-to-many relationship via the 'course_user' pivot table
        return $this->belongsToMany(Course::class, 'course_user');
    }

public function enrolledCourses()
    {
        // Assumes a 'course_user' pivot table
        return $this->belongsToMany(Course::class, 'course_user', 'user_id', 'course_id')->withTimestamps();
    }


        public function centers()
        {
            return $this->belongsToMany(Center::class, 'center_tutor', 'tutor_id', 'center_id');
        }


}