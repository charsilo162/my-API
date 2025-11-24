<?php

namespace Database\Factories;

use App\Models\Price;
use App\Models\Course; // Import the Course model
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Price::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Get all existing Course IDs and select a random one
        $courseId = Course::pluck('id')->random();

        return [
            // Use an existing Course ID
            'course_id' => $courseId,
            
            'amount' => $this->faker->randomFloat(2, 20, 500),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'NGN']),
            
            // Set price as current by default
            'is_current' => true,
        ];
    }
    
    /**
     * State for old/historical prices.
     */
    public function old(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => false,
        ]);
    }
}