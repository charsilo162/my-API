<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Http\Resources\VideoResource;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminVideoController extends Controller
{

   protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }
    
    public function index(Request $request)
    {
        //$query = Video::with('courses'); // Eager load the owner info
        $query = Video::withoutGlobalScope('active')->with('courses'); 
        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%$search%");
        }

        return VideoResource::collection($query->latest()->paginate(20));
    }

    public function toggleActive(Video $video)
        {
           Log::info('Admin Toggle Hit', [
                'video_id' => $video->id,
                'old_status' => $video->is_active,
            ]);
            $video->update([
                'is_active' => !$video->is_active
            ]);
        Log::info('Admin Toggle Success', [
                'video_id' => $video->id,
                'new_status' => $video->is_active,
            ]);
            return response()->json([
                'message' => 'Video visibility status updated',
                'is_active' => $video->is_active
            ]);
        }
    

     public function destroy(Video $video)
    {
        // Ensure the logged-in user is the owner/uploader
        // if ($video->uploader_user_id !== auth()->id()) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        // Delete video file if exists
        if ($video->video_url) {
            $this->cloudinaryService->deleteFile($video->video_url, 'video');
        }

        // Delete thumbnail file if exists
        if ($video->thumbnail_url) {
            $this->cloudinaryService->deleteFile($video->thumbnail_url);
        }

        // Delete the database record
        $video->delete();

        return response()->json(['message' => 'Video and cloud files deleted successfully']);
    }
}