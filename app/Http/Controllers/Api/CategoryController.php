<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
   public function index(Request $request)
    {
        $query = Category::query();

        // Search (for all components)
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%$search%")
                  ->orWhere('slug', 'like', "%$search%");
        }

        // With counts (e.g., for popular: courses_count)
        if ($withCount = $request->query('with_count')) {
            $query->withCount($withCount);
        }

        // Order by (e.g., courses_count desc for popular)
       if ($orderBy = $request->query('order_by')) {
            if (str_contains($orderBy, ',')) {
            [$field, $dir] = explode(',', $orderBy);
            } else {
            [$field, $dir] = explode(' ', $orderBy);
            }
            $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($field, $dir);
     }

        // Limit (for initial loads)
        if ($limit = $request->query('limit')) {
            $query->limit($limit);
        }

        // Pagination if needed (though your components use limit, not paginate)
        $categories = $query->get(); // Use get() for simple lists

        return CategoryResource::collection($categories);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $payload = [
            'name' => $data['name'],
            'slug' => \Str::slug($data['name']),
        ];

        if ($request->hasFile('thumbnail')) {
            $payload['thumbnail_url'] = $request->file('thumbnail')->store('categories', 'public');
        }

        $category = Category::create($payload);

        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $payload = [];

        if (isset($data['name'])) {
            $payload['name'] = $data['name'];
            $payload['slug'] = \Str::slug($data['name']);
        }

        if ($request->hasFile('thumbnail')) {
            if ($category->thumbnail_url) {
                Storage::disk('public')->delete($category->thumbnail_url);
            }
            $payload['thumbnail_url'] = $request->file('thumbnail')->store('categories', 'public');
        }

        $category->update($payload);

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        if ($category->thumbnail_url) {
            Storage::disk('public')->delete($category->thumbnail_url);
        }
        $category->delete();

        return response()->json(['message' => 'Deleted']);
    }


    public function count(Request $request)
{
    $query = Category::query();

    // Optional: filter by search (same logic as index)
    if ($search = $request->query('search')) {
        $query->where('name', 'like', "%$search%")
              ->orWhere('slug', 'like', "%$search%");
    }

    $total = $query->count();

    return response()->json([
        'total' => $total,
        'search' => $search ?? null,
    ]);
}
}