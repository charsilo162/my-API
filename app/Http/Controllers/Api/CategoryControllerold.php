<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->where('is_course_category', true)
            ->withCount('courses') // ← This gives `courses_count`
            ->select('id', 'name', 'slug')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'courses_count' => $cat->courses_count,
            ]);

        return response()->json($categories);
    }
}