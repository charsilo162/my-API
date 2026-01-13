<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\CenterResource;
use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudinaryService;

class CenterController extends Controller
{
   // app/Http/Controllers/Api/CenterController.php
protected $cloudinaryService;
public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }
public function index(Request $request)
{
    $query = Center::query();

    // 1. Search
    if ($search = $request->query('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhere('address', 'like', "%$search%")
              ->orWhere('city', 'like', "%$search%")
              ->orWhereHas('courses', fn($cq) => $cq->where('title', 'like', "%$search%"));
        });
    }

    // 2. Eager load courses + category (for resource)
    $query->with(['courses' => fn($q) => $q->latest()->with('category')->limit(3)]);

    // 3. Order
    $query->orderByDesc('years_of_experience');

    // 4. Featured vs Paginated
    if ($request->boolean('featured')) {
        $limit = $request->query('limit', 3);
        $centers = $query->limit($limit)->get();
        $total = Center::query()->count(); // Still needed? → We'll fix below
    } else {
        $centers = $query->paginate($request->query('per_page', 10));
        $total = $centers->total();
    }

    return response()->json([
        'data' => CenterResource::collection($centers),
        'total' => $total ?? null,
        'featured' => $request->boolean('featured'),
    ]);
}

    public function show(Center $center)
    {
        return new CenterResource($center);
    }

  public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0',
            'center_thumbnail_url' => 'nullable|image|max:2048', // Increased size for Cloudinary
        ]);

        $data = $validated;

        if ($request->hasFile('center_thumbnail_url')) {
            // Use the reusable service
            $url = $this->cloudinaryService->uploadFile(
                $request->file('center_thumbnail_url'), 
                'centers'
            );
            $data['center_thumbnail_url'] = $url;
        }

        $center = Center::create($data);

        $tutorId = auth()->id();
        if ($tutorId) {
            $center->tutors()->attach($tutorId);
        }

        return new CenterResource($center);
    }

   public function update(Request $request, Center $center)
{
    $validated = $request->validate([
        'center_thumbnail_url' => 'nullable|image|max:2048',
         'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'years_of_experience' => 'sometimes|required|integer|min:0',
           
    ]);

    $data = $validated;

    if ($request->hasFile('center_thumbnail_url')) {
        // 1. Delete the old image from Cloudinary
        if ($center->center_thumbnail_url) {
            $this->cloudinaryService->deleteFile($center->center_thumbnail_url);
        }

        // 2. Upload the new image
        $url = $this->cloudinaryService->uploadFile(
            $request->file('center_thumbnail_url'), 
            'centers'
        );
        $data['center_thumbnail_url'] = $url;
    }

    $center->update($data);
    return new CenterResource($center);
}
      public function destroy(Center $center)
        {
            // Delete image from Cloudinary if it exists
            if ($center->center_thumbnail_url) {
                $this->cloudinaryService->deleteFile($center->center_thumbnail_url);
            }

            $center->courses()->detach();
            $center->delete();

            return response()->json(['message' => 'Deleted successfully']);
        }

    public function count(Request $request)
{
    $query = Center::query();

    if ($search = $request->query('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhere('address', 'like', "%$search%")
              ->orWhere('city', 'like', "%$search%")
              ->orWhereHas('courses', fn($cq) => $cq->where('title', 'like', "%$search%"));
        });
    }

    return response()->json(['total' => $query->count()]);
}
}