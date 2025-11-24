<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CenterResource extends JsonResource
{
    // app/Http/Resources/CenterResource.php
// app/Http/Resources/CenterResource.php

public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'slug' => $this->slug,
        'address' => $this->address,
        'city' => $this->city,
        'years_of_experience' => $this->years_of_experience,
        'image_url' => $this->center_thumbnail_url 
            ? asset('storage/' . $this->center_thumbnail_url)
            : asset('storage/img2.png'),

        // ADD THIS: Latest 3 courses with category name
        'latest_courses' => $this->whenLoaded('courses', function () {
            return $this->courses
                ->sortByDesc('created_at')
                ->take(3)
                ->map(function ($course) {
                    return [
                        'category_name' => $course->category?->name ?? 'Uncategorized',
                    ];
                });
        }),
    ];
}
}