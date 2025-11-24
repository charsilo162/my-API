<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\Tutor; // Import the Tutor model
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Video::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Get all existing Tutor IDs and select a random one
        $tutorId = Tutor::pluck('id')->random();

        return [
            // Ensure a Tutor exists as the owner/uploader (using an existing ID)
            'tutor_id' => $tutorId, 
            
            'title' => $this->faker->sentence(3) . ' | Lesson ' . $this->faker->randomNumber(2),
            
            // Use a placeholder URL for testing
            'video_url' => 'https://example.com/videos/' . $this->faker->uuid() . '.mp4', 
            
            'duration' => $this->faker->numberBetween(300, 3600), // 5 minutes to 1 hour
           
        ];
    }
}