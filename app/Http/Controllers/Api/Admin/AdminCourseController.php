<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class AdminCourseController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function index(Request $request)
        {
            
                    $query = Course::withoutGlobalScope('active'); 

                        // 1. Admin Filters (Now you can filter specifically for blocked ones if you want)
                        if ($request->has('is_active')) {
                            $query->where('is_active', $request->boolean('is_active'));
                        }

            // 1. Admin Filters
            if ($request->has('published')) {
                $query->where('publish', $request->boolean('published'));
            }

            if ($search = $request->query('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%")
                    ->orWhereHas('centers', fn($cq) => $cq->where('name', 'like', "%$search%"));
                });
            }

            // 2. Load ALL the relationships you use in the frontend
            $query->with([
                'currentPrice', 
                'centers', 
                'category',
                'videos' => fn($q) => $q->orderByPivot('order_index')->withPivot('order_index')
            ])->withCount([
                'users as registered_count',
                'comments as comments_count',
                'shares as shares_count',
                'likes as likes_count' => fn($q) => $q->where('type', 'up'),
                'likes as dislikes_count' => fn($q) => $q->where('type', 'down'),
                'ratings as ratings_count',
            ])->withAvg('ratings', 'rating');

            // 3. Return latest first
            return CourseResource::collection($query->latest()->paginate($request->query('per_page', 15)));
        }

    // Force delete any course and its cloud assets
    public function destroy(Course $course)
    {
        if ($course->image_thumbnail_url) {
            $this->cloudinaryService->deleteFile($course->image_thumbnail_url);
        }

        $course->centers()->detach();
        $course->delete();

        return response()->json(['message' => "Admin: Course '{$course->title}' deleted."]);
    }

    // "Disable" a course by unpublishing it
   public function toggleActive(Course $course)
            {
                $course->update([
                    'is_active' => !$course->is_active
                ]);

                return response()->json([
                    'message' => 'Course status updated',
                    'is_active' => $course->is_active
                ]);
            }
}