<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
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

    return response()->json([
        'message' => 'Logged in successfully',
        'token' => $token,
        'user' => $user
    ]);

    
}
 public function logout()
{
    \Log::alert('API LOGOUT HIT — USER ID: ' . auth()->id());
    \Log::info('Tokens before delete:', ['count' => auth()->user()->tokens()->count()]);

    auth()->user()->tokens()->delete();

    \Log::alert('ALL TOKENS DELETED — LOGOUT SUCCESSFUL');

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