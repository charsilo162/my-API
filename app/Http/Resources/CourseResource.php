<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request)
{
    $priceAmount    = $this->currentPrice?->amount ?? 0;
    $priceFormatted = $priceAmount == 0 ? 'Free' : '₦' . number_format($priceAmount);

    return [
        'id'          => $this->id,
        'title'       => $this->title,
        'slug'        => $this->slug,
        'description' => $this->description ?? 'No description available.',
        'type'        => $this->type,
        'publish'     => (bool) $this->publish,

        'image_thumbnail_url' => $this->image_thumbnail_url
            ? asset('storage/' . $this->image_thumbnail_url)
            : asset('storage/img3.png'),

        'category' => $this->whenLoaded('category', fn() => [
            'id'   => $this->category->id,
            'name' => $this->category->name,
            'slug' => $this->category->slug,
        ]),

        'centers' => $this->whenLoaded('centers', fn() => $this->centers->map(fn($c) => [
            'id'   => $c->id,
            'name' => $c->name,
            'city' => $c->city,
        ])),

        'first_video' => $this->whenLoaded('videos', fn() => $this->videos->first() ? [
            'id'          => $this->videos->first()->id,
            'order_index' => $this->videos->first()->pivot->order_index ?? 1,
        ] : null),

        'current_price' => [
            'amount'    => $priceAmount,
            'formatted' => $priceFormatted,
        ],

        'registered_count' => $this->registered_count ?? 0,
        'comments_count'   => $this->comments_count ?? 0,
        'likes_count'      => $this->likes_count ?? 0,
        'dislikes_count'   => $this->dislikes_count ?? 0,
        'views_count'      => $this->views_count ?? 0,
        'rating'           => $this->average_rating ?? 4.34,
        'price_formatted'  => $priceFormatted,
        'badge'            => $this->whenLoaded('videos') && $this->videos->isNotEmpty()
            ? 'PART ' . ($this->videos->first()->pivot->order_index ?? 1)
            : 'PART 1',
        'videos' => VideoResource::collection($this->whenLoaded('videos')),
        // THIS LINE IS NOW 100% BULLETPROOF — NO MissingValue EVER AGAIN
        'url_data' => [
            'type'      => $this->type,
            'slug'      => $this->slug,
            'center_id' => $this->relationLoaded('centers') && $this->centers && $this->centers->isNotEmpty()
                ? $this->centers->first()->id
                : null,
        ],
    ];
}
}