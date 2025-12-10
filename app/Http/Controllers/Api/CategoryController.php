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

        // Handle with_count (e.g., 'courses')
        if ($withCount = $request->query('with_count')) {
            $relations = explode(',', $withCount);
            $query->withCount($relations);
        }

        // Search
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        // Order by (e.g., 'courses_count,desc')
        if ($orderBy = $request->query('order_by')) {
            [$field, $direction] = explode(',', $orderBy);
            $query->orderBy($field, $direction ?? 'asc');
        }

        // Limit (for non-paginated, limited fetches like top 6)
        if ($limit = $request->query('limit')) {
            $categories = $query->limit($limit)->get();
            return CategoryResource::collection($categories);
        }

        // Fallback to pagination if no limit
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        $categories = $query->paginate($perPage, ['*'], 'page', $page);

        return CategoryResource::collection($categories);
    }
    public function store(Request $request)
    {
    //     \Log::info('FILES:', $request->allFiles());
    // \Log::info('INPUT:', $request->all());
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
        \Log::info('FILES:', $request->allFiles());
    \Log::info('INPUT:', $request->all());
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
    // 1. Check for associated courses
    if ($category->courses()->exists()) {
        // Option A: Reassign or Error (Safest)
        return response()->json([
            'message' => 'Cannot delete category. It has ' . $category->courses()->count() . ' courses still linked.',
            'action_required' => 'Reassign or delete the courses first.',
        ], 409); // Use 409 Conflict

        /*
        // OPTION B: Mass Delete Related Courses (Use with caution!)
        // $category->courses()->delete(); 
        */
    }
    
    // 2. Delete the thumbnail (as you already do)
    if ($category->thumbnail_url) {
        Storage::disk('public')->delete($category->thumbnail_url);
    }
    
    // 3. Delete the category itself
    $category->delete();

    return response()->json(['message' => 'Category and its files successfully deleted.'], 200);
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