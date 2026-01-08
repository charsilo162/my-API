<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Center;
use App\Models\Tutor;
use App\Models\Course;
use App\Models\Video;
use App\Models\Price; // Assuming you have a Price model

use Illuminate\Database\Seeder;
use Faker\Factory as Faker; // Import Faker if not using $this->faker() globally

class DatabaseSeeder extends Seeder
{
    // Initialize Faker if needed outside of closure (or rely on $this->faker())
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    public function run(): void
    {
        // 1. Initial Data Generation
        User::factory(10)->create(); 
        Category::factory(5)->create();
        $centers = Center::factory(1)->create();

        // Tutor::factory(25)->create(); 
        // $tutors = Tutor::all(); 
        
        // $courses = Course::factory(100)->create(); 
        // $videos = Video::factory(50)->create(); 

        // // 2. Attach Course Prices
        // $courses->each(function (Course $course) {
        //     Price::factory()->for($course)->create();
        // });

        // // 3. Attach Relationships (Pivot Tables)

        // // A. Course <-> Video (UPDATED: Handles Many-to-Many pivot data with order_index)
        // $courses->each(function (Course $course) use ($videos) {
        //     // Select a random number of videos to attach (e.g., 5 to 10 lessons)
        //     $selectedVideos = $videos->random(rand(5, 10));

        //     $pivotData = [];
        //     $orderIndex = 1;
            
        //     // Loop through the selected videos to assign a sequential order_index
        //     foreach ($selectedVideos as $video) {
        //         // The key is the video_id, and the value is an array of pivot attributes
        //         $pivotData[$video->id] = [
        //             'order_index' => $orderIndex,
        //         ];
        //         $orderIndex++;
        //     }

        //     // Attach the videos with the sequenced pivot data
        //     $course->videos()->attach($pivotData);
        // });


        // // B. Center <-> Course (Physical Training Locations)
        // $centers->each(function (Center $center) use ($courses) {
        //     $center->courses()->attach(
        //         $courses->random(rand(3, 8))->pluck('id'),
        //         [
        //             // NOTE: Use $this->faker for RandomFloat if not initialized globally
        //             'price' => $this->faker->randomFloat(2, 50, 500),
        //             'start_date' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
        //         ]
        //     );
        // });

        // // C. Center <-> Tutor (Instructors at a location)
        // $centers->each(function (Center $center) use ($tutors) {
        //     $center->tutors()->attach(
        //         $tutors->random(rand(1, 3))->pluck('id')
        //     );
        // });

        // --- STEP 4: Polymorphic Interactions ---
        // $this->call([
        //     \Database\Seeders\InteractionSeeder::class, 
        // ]);
    }
}