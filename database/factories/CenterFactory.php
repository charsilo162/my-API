<?php

namespace Database\Factories;

use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterFactory extends Factory
{
    protected $model = Center::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Training Center',
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'description' => $this->faker->paragraph(),
            'years_of_experience' => $this->faker->numberBetween(5, 30),
            'center_thumbnail_url' => $this->faker->imageUrl(640, 480, 'school', true),
        ];
    }
}