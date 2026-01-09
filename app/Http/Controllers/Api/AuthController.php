<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
class AuthController extends Controller
{
    
    public function register(Request $request)
{
      \Log::info('=== COURSE UPDATE REQUEST START ===', [
    
        'user_id'   => auth()->id(),
        'input'     => $request->all(),
        'files'     => $request->allFiles() ? array_keys($request->allFiles()) : [],
    ]);
    // 1. Add 'photo' validation rule
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'type' => 'required|in:user,center,tutor',
        'password' => 'required|min:6|confirmed',
        'photo' => 'nullable|image|max:2048', // Optional image, max 2MB
    ]);

    // Initialize an array for user creation data
    $userData = [
        'name' => $data['name'],
        'type' => $data['type'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ];
    
    // 2. Check if a file exists in the request
              if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('profile_photos', 'public');
        
        // Add the path to the user creation data
        $userData['photo_path'] = $photoPath; 
    }
    $user = User::create($userData);

    $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token ?? null,
        ], 200);
}

    public function updateProfile(Request $request)
        {
            $user = $request->user();
            // \Log::info('Files in request:', $request->allFiles());
            $data = $request->validate([
                'name'     => 'sometimes|string|max:255',
                'email'    => 'sometimes|email|unique:users,email,' . $user->id,
                // 'type'     => 'sometimes|in:user,center,tutor',
                'password' => 'sometimes|min:6|confirmed',
                'photo'    => 'sometimes|nullable|image|max:2048',
            ]);

            if ($request->has('password')) {
                $data['password'] = Hash::make($data['password']);
            }

            // This will now work because request is POST
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($user->photo_path) {
                    Storage::disk('public')->delete($user->photo_path);
                }

                $data['photo_path'] = $request->file('photo')->store('profile_photos', 'public');
            }
            unset($data['photo']);
            $user->update($data);

          return response()->json([
                'user' => new UserResource($user->fresh()),
            ], 200);
        }
        public function login(Request $request)
        {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('spa')->plainTextToken;
                Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'token' => $token,
            ]);
            return response()->json([
                'message' => 'Logged in successfully',
                'token' => $token,
                'user' => new UserResource($user),
            ]);

            
        }
 public function logout()
{
    \Log::alert('API LOGOUT HIT — USER ID: ' . auth()->id());
    \Log::info('Tokens before delete:', ['count' => auth()->user()->tokens()->count()]);

    auth()->user()->tokens()->delete();

 

    return response()->json(['message' => 'Logged out successfully']);
}

    public function enrolledCourses(Request $request)
{
    $user = $request->user();
    $query = $user->enrolledCourses();

    if ($search = $request->query('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%$search%")
              ->orWhere('description', 'like', "%$search%");
        });
    }

    $courses = $query->with('videos')->paginate(9);

    return CourseResource::collection($courses);
}
}