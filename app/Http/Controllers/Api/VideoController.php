<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{

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

// public function show(Video $video)
// {
//     $this->authorizeVideo($video);
//     return new VideoResource($video);
// }


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

        $videoPath = $request->file('video_file')->store('videos', 'public');
        $thumbUrl = null;

        if ($request->hasFile('thumbnail_file')) {
            $thumbPath = $request->file('thumbnail_file')->store('thumbnails', 'public');
            $thumbUrl = Storage::url($thumbPath);
        }

        $video = Video::create([
            'uploader_user_id' => auth()->id(),
            'title'            => $request->title,
            'video_url'        => Storage::url($videoPath),
            'thumbnail_url'    => $thumbUrl,
            'duration'         => $request->duration,
        ]);

        $course = Course::find($request->course_id);
        $course->videos()->syncWithoutDetaching([
            $video->id => ['order_index' => $request->input('order_index', 0)]
        ]);

        return new VideoResource($video);
    }

// public function update(Request $request, $id)
// {
//     // Load video or fail
//     $video = Video::findOrFail($id);

//     // Authorization check
//     // if ($video->uploader_user_id !== auth()->id()) {
//     //     return response()->json(['error' => 'Unauthorized'], 403);
//     // }

//     // Validate fields
//     $validated = $request->validate([
//         'title' => 'required|string|max:255',
//         'duration' => 'nullable|integer|min:1',
//         'publish' => 'sometimes|boolean',
//     ]);

//     // Update video
//     $video->update($validated);

//     return new VideoResource($video);
// }

public function update(Request $request, Video $video)
{
    // if ($video->uploader_user_id !== auth()->id()) {
    //     return response()->json(['error' => 'Unauthorized'], 403);
    // }

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'duration' => 'nullable|integer|min:1',
        'publish' => 'sometimes|boolean',
    ]);

    $video->update($validated);

    return new VideoResource($video);
}

// public function destroy(Video $video)
// {
//     $this->authorizeVideo($video);

//     if ($video->video_url) {
//         $path = str_replace('/storage/', '', parse_url($video->video_url, PHP_URL_PATH));
//         Storage::disk('public')->delete($path);
//     }
//     if ($video->thumbnail_url) {
//         $path = str_replace('/storage/', '', parse_url($video->thumbnail_url, PHP_URL_PATH));
//         Storage::disk('public')->delete($path);
//     }

//     $video->delete();

//     return response()->json(['message' => 'Video deleted']);
// }

public function destroy(Video $video)
{
    // Ensure the logged-in user is the owner/uploader
    // if ($video->uploader_user_id !== auth()->id()) {
    //     return response()->json(['error' => 'Unauthorized'], 403);
    // }

    // Delete video file if exists
    if ($video->video_url) {
        $path = str_replace('/storage/', '', parse_url($video->video_url, PHP_URL_PATH));
        Storage::disk('public')->delete($path);
    }

    // Delete thumbnail file if exists
    if ($video->thumbnail_url) {
        $path = str_replace('/storage/', '', parse_url($video->thumbnail_url, PHP_URL_PATH));
        Storage::disk('public')->delete($path);
    }

    // Delete the database record
    $video->delete();

    return response()->json(['message' => 'Video deleted successfully']);
}


// public function togglePublish(Video $video)
// {
//     $this->authorizeVideo($video);
//     $video->update(['publish' => !$video->publish]);
//     return new VideoResource($video);
// }
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