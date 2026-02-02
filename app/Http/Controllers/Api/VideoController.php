<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Course;
use App\Models\Video;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{

    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }


    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $tutorId = $user->tutor?->id ?? $user->tutor_id ?? $userId;

        $query = Video::where('uploader_user_id', $userId)
                    ->orWhere('uploader_user_id', $tutorId);

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%$search%");
        }

        $videos = $query->latest()->paginate(12);

        return VideoResource::collection($videos);
    }




    public function show($id)
    {
        // Load video or fail
        $video = Video::findOrFail($id);

        // Authorization check
        // if ($video->uploader_user_id !== auth()->id()) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        return new VideoResource($video);
    }
        public function store(Request $request)
    {
        $request->validate([
            'course_id'      => 'required|exists:courses,id',
            'title'          => 'required|string|max:255',
            'video_file'     => 'required|file|mimes:mp4,mov,avi,wmv|max:102400',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'duration'       => 'nullable|integer',
            'order_index'    => 'nullable|integer|min:0',
        ]);

        $videoUrl = $this->cloudinaryService->uploadFile(
            $request->file('video_file'), 
            'course_videos', 
            'video'
        );

        $thumbUrl = null;

        if ($request->hasFile('thumbnail_file')) {
            $thumbUrl = $this->cloudinaryService->uploadFile(
                $request->file('thumbnail_file'), 
                'video_thumbnails'
            );
        }

        $video = Video::create([
            'uploader_user_id' => auth()->id(),
            'title'            => $request->title,
            'video_url'        => $videoUrl,
            'thumbnail_url'    => $thumbUrl,
            'duration'         => $request->duration,
        ]);

        $course = Course::find($request->course_id);
        $course->videos()->syncWithoutDetaching([
            $video->id => ['order_index' => $request->input('order_index', 0)]
        ]);

        return new VideoResource($video);
    }

    public function update(Request $request, Video $video)
    {
        // 1. Log the raw incoming request
        \Log::info('=== VIDEO UPDATE START ===', [
            'video_id' => $video->id,
            'method' => $request->method(),
            'all_input' => $request->except(['video_file', 'thumbnail_file']), // log text only
            'has_video' => $request->hasFile('video_file') ? 'YES' : 'NO',
            'has_thumbnail' => $request->hasFile('thumbnail_file') ? 'YES' : 'NO',
        ]);

        try {
            $validated = $request->validate([
                'title'          => 'sometimes|required|string|max:255',
                'duration'       => 'nullable|integer|min:1',
                'publish'        => 'sometimes|boolean',
                'video_file'     => 'nullable|file|mimes:mp4,mov,avi,wmv|max:102400',
                'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);
            \Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation FAILED', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        $updateData = $validated;
        unset($updateData['video_file'], $updateData['thumbnail_file']);

        // 2. Handle Video File
        if ($request->hasFile('video_file')) {
            \Log::info('Processing Video File...');
            if ($video->video_url) {
                $this->cloudinaryService->deleteFile($video->video_url, 'video');
                \Log::info('Old video deleted from Cloudinary');
            }

            $videoUrl = $this->cloudinaryService->uploadFile(
                $request->file('video_file'), 
                'course_videos', 
                'video'
            );

            if ($videoUrl) {
                $updateData['video_url'] = $videoUrl;
                \Log::info('New Video Uploaded Successfully', ['url' => $videoUrl]);
            } else {
                \Log::error('Video upload to Cloudinary returned NULL');
            }
        }

        // 3. Handle Thumbnail File
        if ($request->hasFile('thumbnail_file')) {
            \Log::info('Processing Thumbnail File...');
            if ($video->thumbnail_url) {
                $this->cloudinaryService->deleteFile($video->thumbnail_url);
                \Log::info('Old thumbnail deleted from Cloudinary');
            }

            $thumbUrl = $this->cloudinaryService->uploadFile(
                $request->file('thumbnail_file'), 
                'video_thumbnails'
            );

            if ($thumbUrl) {
                $updateData['thumbnail_url'] = $thumbUrl;
                \Log::info('New Thumbnail Uploaded Successfully', ['url' => $thumbUrl]);
            } else {
                \Log::error('Thumbnail upload to Cloudinary returned NULL');
            }
        }

        // 4. Log final data before DB update
        \Log::info('Final Database Update Data:', $updateData);

        $status = $video->update($updateData);

        \Log::info('Database update status: ' . ($status ? 'SUCCESS' : 'FAILED'));

        return new VideoResource($video->fresh());
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



    public function togglePublish(Video $video)
    {
        // Ensure the logged-in user is the owner/uploader
        // if ($video->uploader_user_id !== auth()->id()) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        // Toggle the publish status
        $video->publish = !$video->publish;
        $video->save();

        return new VideoResource($video);
    }

private function authorizeVideo(Video $video)
{
    $userId = auth()->id();
    $tutorId = auth()->user()?->tutor?->id ?? $userId;

    if (!in_array($video->uploader_user_id, [$userId, $tutorId])) {
        abort(403);
    }
}
}