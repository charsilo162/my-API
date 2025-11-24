<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
public function index(Request $request)
{
    $query = Course::query();

    // 1. Publish filter — only published by default
    if (! $request->boolean('include_unpublished')) {
        $query->where('publish', true);
    }

    // 2. Category slug
    if ($categorySlug = $request->query('category')) {
        $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
    }

    // 3. Tutor ID
    if ($tutorId = $request->query('tutor')) {
        $query->where('assigned_tutor_id', $tutorId);
    }

    // 4. Course type
    if ($type = $request->query('type', $request->query('filterType'))) {
        $query->where('type', $type);
    }

    // 5. Price range
    if ($priceRange = $request->query('price')) {
        [$min, $max] = explode('-', $priceRange);
        $min = (int) $min;
        $max = (int) $max;

        $query->whereHas('currentPrice', function ($q) use ($min, $max) {
            $q->where('amount', '>=', $min);
            if ($max > 0) {
                $q->where('amount', '<=', $max);
            }
        });
    }

    // 6. Location search
    if ($location = $request->query('location')) {
        $query->whereHas('centers', fn($q) => $q->where('address', 'like', "%$location%"));
    }

    // 7. Center ID (for RelatedCoursesByCenter)
    if ($centerId = $request->query('center_id')) {
        $query->whereHas('centers', fn($q) => $q->where('centers.id', $centerId));
    }

    // 8. Uploader ID (My Courses)
    if ($uploaderId = $request->query('uploader')) {
        $query->where('uploader_user_id', $uploaderId);
    }

    // 9. Search by title
    if ($search = $request->query('search')) {
        $query->where('title', 'like', "%$search%");
    }

    // 10. Random order
    if ($request->boolean('random')) {
        $query->inRandomOrder();
    }

    // Eager load relations & counts
    $query->with([
        'currentPrice',
        'centers',
        'category',
        'videos' => fn($q) => $q->orderByPivot('order_index')->withPivot('order_index')->limit(1)
    ])
    ->withCount([
        'users as registered_count',
        'comments as comments_count',
        'likes as likes_count' => fn($q) => $q->where('type', 'up'),
        'likes as dislikes_count' => fn($q) => $q->where('type', 'down'),
    ]);

    // Pagination or limit
    if ($request->boolean('paginate')) {
        $courses = $query->paginate($request->query('per_page', 6));
    } else {
        $limit = $request->query('limit', 6);
        $courses = $query->limit($limit)->get();
    }

    return CourseResource::collection($courses);
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'title' => 'required|string|max:100|unique:courses,title',
            'description' => 'required|string',
            'type' => 'required|in:physical,online',
            'center_id' => $request->type === 'physical' ? 'required|integer|exists:centers,id' : 'nullable',
            'image_thumb' => 'nullable|image|max:1024',
            'publish' => 'boolean',
        ]);

        $data = $validated;
        $data['uploader_user_id'] = auth()->id();
        $data['publish'] = $validated['publish'] ?? false;

        if ($request->hasFile('image_thumb')) {
            $data['image_thumbnail_url'] = $request->file('image_thumb')->store('courses', 'public');
        }

        $course = Course::create($data);

        if ($request->type === 'physical' && $request->center_id) {
            $course->centers()->attach($request->center_id, [
                'price' => null,
                'start_date' => null,
                'end_date' => null,
            ]);
        }

        return new CourseResource($course->fresh('centers'));
    }
public function edit($id)  // ← Remove model binding!
{
    $course = Course::with(['centers', 'category', 'currentPrice'])
                    ->findOrFail($id);  // ← Direct ID lookup, no slug nonsense

    // Optional: extra security
    if ($course->uploader_user_id !== auth()->id()) {
        abort(403);
    }

    return new CourseResource($course);
}

    // public function show(Course $course)
    // {
    //     $course->load('centers');
    //     return new CourseResource($course);
    // }

public function show(Course $course)
{
    // \Log::info('=== HIT show METHOD FOR COURSE: ' . $course->id . ' ===');
    $course->load(['centers', 'videos', 'category', 'currentPrice']);
    return new CourseResource($course);
}
    /**
 * Update course using raw ID — completely bypasses slug binding
 */
public function update(Request $request, $id)
{
    \Log::info('=== COURSE UPDATE REQUEST START ===', [
        'course_id' => $id,
        'user_id'   => auth()->id(),
        'input'     => $request->all(),
        'files'     => $request->allFiles() ? array_keys($request->allFiles()) : [],
    ]);

    $course = Course::findOrFail($id);

    if ($course->uploader_user_id !== auth()->id()) {
        \Log::warning('Unauthorized course edit attempt', ['course_id' => $id, 'user_id' => auth()->id()]);
        abort(403, 'Unauthorized');
    }

    \Log::info('Course found & authorized', ['course' => $course->toArray()]);

    try {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'title'       => 'sometimes|required|string|max:100|unique:courses,title,' . $course->id,
            'description' => 'sometimes|required|string',
            'type'        => 'sometimes|required|in:physical,online',
            'center_id'   => 'nullable|integer|exists:centers,id',
            'image_thumb' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'publish'     => 'sometimes|boolean',
        ]);

        \Log::info('Validation passed', $validated);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Validation failed', $e->errors());
        throw $e;
    }

    $data = $validated;

    // Auto-generate slug
    if (isset($data['title'])) {
        $data['slug'] = \Str::slug($data['title']);
        \Log::info('Slug generated', ['slug' => $data['slug']]);
    }

    // Handle image upload
    if ($request->hasFile('image_thumb')) {
        $file = $request->file('image_thumb');
        \Log::info('Image received', [
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
        ]);

        if ($course->image_thumbnail_url) {
            \Storage::disk('public')->delete($course->image_thumbnail_url);
            \Log::info('Old image deleted', ['path' => $course->image_thumbnail_url]);
        }

        $path = $file->store('courses', 'public');
        $data['image_thumbnail_url'] = $path;
        \Log::info('New image stored', ['path' => $path]);
    }

    // Final data to be saved
    \Log::info('Final data for update', $data);

    // Update the course
    $course->update($data);
    \Log::info('Course updated in DB', $course->fresh()->toArray());

    // Handle centers
    if ($request->filled('type')) {
        if ($request->type === 'physical' && $request->filled('center_id')) {
            $course->centers()->sync([$request->center_id]);
            \Log::info('Center attached', ['center_id' => $request->center_id]);
        } elseif ($request->type === 'online') {
            $course->centers()->detach();
            \Log::info('All centers detached (online course)');
        }
    }

    $freshCourse = $course->fresh(['centers', 'category', 'currentPrice']);
    \Log::info('=== COURSE UPDATE SUCCESS ===', $freshCourse->toArray());

    return new CourseResource($freshCourse);
}


    public function destroy(Course $course)
    {
        if ($course->image_thumbnail_url) {
            Storage::disk('public')->delete($course->image_thumbnail_url);
        }

        $course->centers()->detach();
        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }

    public function togglePublish(Course $course)
{
    // Security: Only uploader can toggle
    if ($course->uploader_user_id !== auth()->id()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $course->update(['publish' => !$course->publish]);

    return new CourseResource($course->loadMissing(['currentPrice', 'centers']));
}

// app/Http/Controllers/Api/CourseController.php
public function watch(Course $course)
{
    // Authorization: only enrolled users
    if (!auth()->check() || !$course->users()->where('user_id', auth()->id())->exists()) {
        abort(403, 'You are not enrolled in this course.');
    }

    $course->load([
        'videos' => fn($q) => $q->orderByPivot('order_index')->withPivot('order_index')
    ]);

    return new CourseResource($course);
}

// app/Http/Controllers/Api/CourseController.php

public function noVideos(Request $request)
{
    $query = Course::query()
        ->where(function ($q) {
            $q->where('uploader_user_id', auth()->id())
              ->orWhere('assigned_tutor_id', auth()->id());
        })
        ->with(['category', 'centers'])   // ← THIS LINE MUST BE HERE
        ->withCount('videos')
        ->doesntHave('videos');
    if ($request->filled('search')) {
     $query->where('title', 'like', '%' . $request->search . '%');
 }

 $courses = $query->latest()->paginate(12);

 return CourseResource::collection($courses);
}



public function publish(Course $course)
{
    if (!in_array($course->uploader_user_id, [auth()->id(), auth()->user()?->tutor?->id ?? null])) {
        abort(403);
    }

    $course->update(['publish' => true]);

    return new CourseResource($course);
}
}
