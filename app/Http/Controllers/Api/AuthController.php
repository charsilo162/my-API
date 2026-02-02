<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
     protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }
    
    public function register(Request $request)
    {
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
            $userData['photo_path'] = $this->cloudinaryService->uploadFile(
                $request->file('photo'), 
                'profile_photos'
            ); 
        }

        $user = User::create($userData);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Validate the incoming data, making fields optional
        $data = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'type' => 'in:user,center,tutor',
            'password' => 'min:6|confirmed',
            'photo' => 'nullable|image|max:2048', // Optional image, max 2MB
        ]);

        // Handle password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Handle photo if provided
        if ($request->hasFile('photo')) {
            // Optionally delete the old photo if it exists
            if ($user->photo_path) {
                $this->cloudinaryService->deleteFile($user->photo_path);
            }

            // Upload new photo
            $data['photo_path'] = $this->cloudinaryService->uploadFile(
                $request->file('photo'), 
                'profile_photos'
            );
        }

        // Remove the 'photo' file object from the data array so it doesn't interfere with update
        unset($data['photo']);
        
        // Update the user with the provided data
        $user->update($data);

        return response()->json([
            'user' => $user,
        ], 200);
    }
 public function login(Request $request)
            {
                $credentials = $request->validate([
                    'email' => 'required|email',
                    'password' => 'required|string',
                ]);

                // 1. Check if the credentials are correct
                if (!Auth::attempt($credentials)) {
                    return response()->json(['message' => 'Invalid credentials'], 401);
                }

                $user = Auth::user();

                // 2. CHECK STATUS: Prevent login if is_active is false
                if (!$user->is_active) {
                    // Log out immediately to clear the session
                    Auth::logout(); 
                    
                    return response()->json([
                        'message' => 'Your account has been deactivated. Please contact support.'
                    ], 403); // 403 Forbidden is the standard for blocked access
                }

                // 3. Issue Token only if user is active
                $token = $user->createToken('spa')->plainTextToken;

                return response()->json([
                    'message' => 'Logged in successfully',
                    'token' => $token,
                    'user' => $user
                ]);
            }
        public function logout()
        {
            // \Log::alert('API LOGOUT HIT — USER ID: ' . auth()->id());
            // \Log::info('Tokens before delete:', ['count' => auth()->user()->tokens()->count()]);

            auth()->user()->tokens()->delete();

            //\Log::alert('ALL TOKENS DELETED — LOGOUT SUCCESSFUL');

            return response()->json(['message' => 'Logged out successfully']);
        }

        //     public function enrolledCourses(Request $request)
        // {
        //     $user = $request->user();
        //     $query = $user->enrolledCourses();

        //     if ($search = $request->query('search')) {
        //         $query->where(function ($q) use ($search) { 
        //             $q->where('title', 'like', "%$search%")
        //               ->orWhere('description', 'like', "%$search%");
        //         });
        //     }

        //     $courses = $query->with('videos')->paginate(9);

        //     return CourseResource::collection($courses);
        // }


    public function enrolledCourses(Request $request)
    {
        $request->validate([
            'type' => 'required|in:online,physical,hybrid',
        ]);
        // log::info('Enrolled courses request type: ' . $request->type);
        $user = $request->user();

        $query = $user->enrolledCourses()
            ->where('type', $request->type);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // ✅ Conditional eager loading
        $query->with([
            'videos',
            'centers' => function ($q) use ($request) {
                if (in_array($request->type, ['physical', 'hybrid'])) {
                    $q->select('centers.id', 'centers.name'); // keep it light
                }
            }
        ]);

        return CourseResource::collection(
            $query->paginate(9)
        );
    }

    public function forgotPassword(Request $request)
        {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Password reset link sent to your email'
                ]);
            }

            return response()->json([
                'message' => 'Unable to send reset link'
            ], 500);
        }



    public function resetPassword(Request $request)
            
            {
                $request->validate([
                    'token' => 'required',
                    'email' => 'required|email',
                    'password' => 'required|min:6|confirmed',
                ]);

                $status = Password::reset(
                    $request->only('email', 'password', 'password_confirmation', 'token'),
                    function ($user, $password) {
                        $user->forceFill([
                            'password' => Hash::make($password),
                            'remember_token' => Str::random(60),
                        ])->save();
                    }
                );

                if ($status === Password::PASSWORD_RESET) {
                    return response()->json(['message' => 'Password reset successful']);
                }

                return response()->json(['message' => 'Invalid token or email'], 400);
            }


}