<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Rating;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;


class RatingController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'resource_type' => 'required|string',
            'resource_id'   => 'required|integer',
        ]);

        $query = Rating::where('rateable_type', $request->resource_type)
            ->where('rateable_id', $request->resource_id);

        return response()->json([
            'average' => round($query->avg('rating'), 1),
            'count'   => $query->count(),
            'user_rating' => auth()->check()
                ? $query->where('user_id', auth()->id())->value('rating')
                : null,
        ]);
    }
    public function loginshow(Request $request)
    {
        $request->validate([
            'resource_type' => 'required|string',
            'resource_id'   => 'required|integer',
        ]);

        $query = Rating::where('rateable_type', $request->resource_type)
            ->where('rateable_id', $request->resource_id);

        return response()->json([
            'average' => round($query->avg('rating'), 1),
            'count'   => $query->count(),
            'user_rating' => auth()->check()
                ? $query->where('user_id', auth()->id())->value('rating')
                : null,
        ]);
    }

    public function rate(Request $request)
    {
        $request->validate([
            'resource_type' => 'required|string',
            'resource_id'   => 'required|integer',
            'rating'        => 'required|integer|min:1|max:5',
        ]);

        Rating::updateOrCreate(
            [
                'user_id'       => auth()->id(),
                'rateable_type' => $request->resource_type,
                'rateable_id'   => $request->resource_id,
            ],
            ['rating' => $request->rating]
        );

        return response()->json(['status' => 'rated']);
    }
}
