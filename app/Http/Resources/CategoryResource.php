<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
     return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail_url' => $this->thumbnail_url ? asset('storage/' . $this->thumbnail_url) : asset('storage/img3.png'), // Default image
            'courses_count' => $this->when(isset($this->courses_count), $this->courses_count, 0), // Include if loaded
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
