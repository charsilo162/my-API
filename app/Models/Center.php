<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Relations\MorphMany;


class Center extends Model

{

    use HasFactory;

    protected $fillable = [
            'name',
            'address',
            'city',
            'description',
            'years_of_experience',
            'center_thumbnail_url',
];
    /**

     * Get the courses physically offered by the Center.

     */

    public function courses(): BelongsToMany

    {

        // Must specify the pivot table and include extra pivot columns

        return $this->belongsToMany(Course::class, 'center_course')

                    ->withPivot(['price', 'start_date', 'end_date'])

                    ->withTimestamps();

    }


    /**

     * Get the Tutors affiliated with the Center.

     */

        // public function tutors(): BelongsToMany

        // {

        //     return $this->belongsToMany(Tutor::class, 'center_tutor');

        // }
    // In app/Models/Center.php

        public function tutors()
        {
            return $this->belongsToMany(User::class, 'center_tutor', 'center_id', 'tutor_id');
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


public function latestCourses()

{

    // The base relationship (Many-to-Many)

    return $this->belongsToMany(Course::class)

                ->withPivot('created_at') // Include the timestamp from the pivot

                ->with('category')        // Eager load the category with this specific relation

                ->orderByDesc('pivot_created_at') // Order by the time they were attached

                ->limit(3);                       // Limit the result set to 3

}



}