<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\CenterResource;
use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CenterController extends Controller
{
   // app/Http/Controllers/Api/CenterController.php

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
   public function store(Request $request)
{
    \Log::info('Request received', [
        'data' => $request->all()
    ]);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'address' => 'required|string|max:255',
        'city' => 'required|string|max:255',
        'years_of_experience' => 'required|integer|min:0',
        'center_thumbnail_url' => 'nullable|image|max:1024',
    ]);

    \Log::info('Validation passed', [
        'validated' => $validated
    ]);

    $data = $validated;

    if ($request->hasFile('center_thumbnail_url')) {
        \Log::info('File detected');
        $data['center_thumbnail_url'] = $request
            ->file('center_thumbnail_url')
            ->store('centers', 'public');
    }

    $center = Center::create($data);

    \Log::info('Center created', [
        'id' => $center->id
    ]);

    return new CenterResource($center);
}

    public function show(Center $center)
    {
        return new CenterResource($center);
    }

    public function update(Request $request, Center $center)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'years_of_experience' => 'sometimes|required|integer|min:0',
            'center_thumbnail_url' => 'nullable|image|max:1024',
        ]);

        $data = $validated;

        if ($request->hasFile('center_thumbnail_url')) {
            if ($center->center_thumbnail_url) {
                Storage::disk('public')->delete($center->center_thumbnail_url);
            }
            $data['center_thumbnail_url'] = $request->file('center_thumbnail_url')->store('centers', 'public');
        }

        $center->update($data);

        return new CenterResource($center);
    }

    public function destroy(Center $center)
    {
        if ($center->center_thumbnail_url) {
            Storage::disk('public')->delete($center->center_thumbnail_url);
        }
        $center->courses()->detach();
        $center->delete();
        return response()->json(['message' => 'Deleted']);
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