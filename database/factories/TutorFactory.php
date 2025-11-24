<?php

namespace Database\Factories;

use App\Models\Tutor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TutorFactory extends Factory
{
    protected $model = Tutor::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Get the IDs of all users who are NOT already associated with a tutor record.
        $nonTutorUserIds = User::doesntHave('tutor')->pluck('id')->toArray();

        // Fallback: If no free users exist, create one (useful if factory runs alone)
        $userId = !empty($nonTutorUserIds) 
                  ? $this->faker->unique()->randomElement($nonTutorUserIds)
                  : User::factory()->create()->id; 

        return [
            // Link to an existing User who hasn't been defined as a Tutor yet
            'user_id' => $userId, 
            
            'bio' => $this->faker->paragraph(2),
            'experience_years' => $this->faker->numberBetween(1, 15),
        ];
    }
}