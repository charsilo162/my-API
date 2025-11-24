<?php

namespace Database\Factories;

use App\Models\Like;
use App\Models\User;
use App\Models\Course;
use App\Models\Video;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

class LikeFactory extends Factory
{
    protected $model = Like::class;

    public function definition(): array
    {
        // 1. Fetch ALL IDs once (using static properties is often faster in factories)
        $users = User::pluck('id');
        $centers = Center::pluck('id');
        $courses = Course::pluck('id');
        $videos = Video::pluck('id');

        $likeables = [
            Course::class => $courses,
            Video::class => $videos,
            Center::class => $centers,
        ];

        // 2. Choose a random likeable model class
        $likeableType = $this->faker->randomElement(array_keys($likeables));
        
        // 3. Select a random existing ID for the chosen model type
        // Ensure the collection is not empty before calling random()
        $likeableIds = $likeables[$likeableType];
        $likeableId = $likeableIds->isNotEmpty() ? $likeableIds->random() : 1; 

        return [
            // Ensure the user ID is selected from existing users
            'user_id' => $users->random(), // Use the collected IDs for efficiency
            
            'likeable_id' => $likeableId,
            'likeable_type' => $likeableType,

            // **NEW ADDITION:** Randomly select the 'type' for the vote
            'type' => $this->faker->randomElement(['up', 'down']),
        ];
    }
}
