<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class VideoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'video_url'     => asset($this->video_url), // Changed: Prepend full URL (e.g., http://yourdomain.com/storage/video/video.mp4)
            'thumbnail_url' => $this->thumbnail_url ? asset($this->thumbnail_url) : null, // Optional: Do the same for thumbnail if needed
            'duration'      => $this->duration,
            'publish'       => (bool) $this->publish,
            'created_at'    => $this->created_at->format('M d, Y'),
            'created_at_iso'=> $this->created_at->toDateTimeString(),
            'slug' => $this->courses->pluck('slug'),
            // Only include pivot if attached to a course (e.g., in CourseWatch)
            'pivot' => $this->whenPivotLoaded('course_video', fn() => [
                'order_index' => $this->pivot->order_index ?? 1,
            ]),

            // Optional: for debugging or future use
            'uploader_user_id' => $this->uploader_user_id,
        ];
    }
}