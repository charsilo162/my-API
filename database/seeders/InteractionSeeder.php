<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Video;
use App\Models\Center;
use App\Models\Comment; 
use App\Models\Share; // <-- Added Share Model
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure all core data exists before running this seeder
        $users = User::pluck('id');
        $centers = Center::all();
        $courses = Course::all();
        $videos = Video::all();

        // Check if data exists
        if ($users->isEmpty()) {
            echo "Skipping InteractionSeeder: No users found.\n";
            return;
        }

        // --- 1. Seed Likes for Centers, Courses, and Videos ---
        $this->seedLikes($centers, $users, Center::class, 5, 20);
        $this->seedLikes($courses, $users, Course::class, 10, 30);
        $this->seedLikes($videos, $users, Video::class, 1, 5);
        
        // --- 2. Seed Comments for Centers, Courses, and Videos ---
        $this->seedComments($centers, Center::class, 5, 15);
        $this->seedComments($courses, Course::class, 10, 25);
        $this->seedComments($videos, Video::class, 3, 10);

        // --- 3. Seed Shares for Centers, Courses, and Videos (New) ---
        $this->seedShares($centers, Center::class, 2, 8);
        $this->seedShares($courses, Course::class, 5, 15);
        $this->seedShares($videos, Video::class, 1, 4);
    }
    
    /**
     * Generic method to seed polymorphic likes using insertOrIgnore.
     */
    protected function seedLikes($likeableItems, $allUserIds, $likeableType, $minLikes, $maxLikes): void
    {
        $likeableItems->each(function ($item) use ($allUserIds, $likeableType, $minLikes, $maxLikes) {
            
            // Randomly select a unique set of users for this item
            $likers = $allUserIds->random(rand($minLikes, $maxLikes));

            $likesData = [];
            $now = now();
            
            foreach ($likers as $userId) {
                $likesData[] = [
                    'user_id' => $userId,
                    'likeable_id' => $item->id,
                    'likeable_type' => $likeableType,
                    // **Updated to include the 'type' column**
                    'type' => rand(0, 1) ? 'up' : 'down',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            
            // Use insertOrIgnore: This instructs the database to skip insertion 
            // if the unique key (user_id, likeable_id, likeable_type) is violated.
            DB::table('likes')->insertOrIgnore($likesData);
        });
    }

    /**
     * Generic method to seed polymorphic comments using the factory.
     */
    protected function seedComments($commentableItems, $commentableType, $minComments, $maxComments): void
    {
        $commentableItems->each(function ($item) use ($commentableType, $minComments, $maxComments) {
            
            // Create a random number of comments (between min and max) for this item
            $commentCount = rand($minComments, $maxComments);

            Comment::factory()
                ->count($commentCount)
                ->state([
                    'commentable_id' => $item->id,
                    'commentable_type' => $commentableType,
                ])
                ->create();
        });
    }

    /**
     * Generic method to seed polymorphic shares using the factory.
     */
    protected function seedShares($shareableItems, $shareableType, $minShares, $maxShares): void
    {
        $shareableItems->each(function ($item) use ($shareableType, $minShares, $maxShares) {
            
            // Create a random number of shares (between min and max) for this item
            $shareCount = rand($minShares, $maxShares);

            Share::factory()
                ->count($shareCount)
                ->state([
                    'shareable_id' => $item->id,
                    'shareable_type' => $shareableType,
                ])
                ->create();
        });
    }
}
