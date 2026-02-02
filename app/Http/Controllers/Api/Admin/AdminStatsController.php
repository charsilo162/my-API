<?php
namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\{Course, Center, User, Video};
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_tutors' => User::where('type')->count(),
            'total_centers' => Center::count(),
            'total_courses' => Course::count(),
            'total_videos' => Video::count(),
            // 'system_revenue' => DB::table('transactions')->sum('amount'), // Example
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }
}