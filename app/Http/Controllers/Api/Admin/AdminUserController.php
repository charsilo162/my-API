<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    // List all users with their roles
    public function index(Request $request)
    {
        //$query = User::query();
        $query = User::withoutGlobalScope('active'); 
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
        }

        return response()->json($query->paginate(20));
    }

    // Disable a user (assuming an 'is_active' or 'status' column)
    public function toggleStatus(User $user)
    {
        // Prevent admin from disabling themselves
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot disable your own account'], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => $user->is_active ? 'User account activated' : 'User account disabled',
            'is_active' => $user->is_active
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot delete yourself'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted permanently']);
    }
}