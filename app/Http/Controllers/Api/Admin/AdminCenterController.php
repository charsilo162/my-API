<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CenterResource;
use App\Models\Center;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class AdminCenterController extends Controller
{
    protected $cloudinaryService;
public function index(Request $request)
    {
       // $query = Center::query();
        $query = Center::withoutGlobalScope('active'); 

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%$search%");
        }

        // Admin sees everything, paginated
        return CenterResource::collection($query->latest()->paginate(15));
    }
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    // DELETE any center
    public function destroy(Center $center)
    {
        if ($center->center_thumbnail_url) {
            $this->cloudinaryService->deleteFile($center->center_thumbnail_url);
        }
        $center->courses()->detach();
        $center->delete();

        return response()->json(['message' => "Admin: Center '{$center->name}' deleted."]);
    }

    // DISABLE any center (Assuming you have an 'is_active' column)
    public function toggleStatus(Center $center)
    {
        $center->is_active = !$center->is_active;
        $center->save();

        return response()->json([
            'message' => $center->is_active ? 'Center enabled' : 'Center disabled',
            'status' => $center->is_active
        ]);
    }
}