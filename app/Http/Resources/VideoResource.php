<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class VideoResource extends JsonResource
{
    // app/Http/Resources/VideoResource.php

        public function toArray($request)
        {
            return [
                'id'               => $this->id,
                'title'            => $this->title,
                'video_url'        => $this->video_url,
                'is_active'        => (bool) $this->is_active,
                'thumbnail_url'    => $this->thumbnail_url ?? asset('storage/default-avatar.png'),
                'duration'         => $this->duration,
                'publish'          => (bool) $this->publish,
                'created_at'       => $this->created_at->format('M d, Y'),
                'created_at_iso'   => $this->created_at->toDateTimeString(),

                // ✅ Course Relationship (loaded for Admin index)
                'courses' => CourseResource::collection($this->whenLoaded('courses')),

                'order_index' => max(1, (int) ($this->pivot->order_index ?? 1)),
                'uploader_user_id' => $this->uploader_user_id,
            ];
        }

}
