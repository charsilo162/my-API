<?php

use App\Http\Controllers\Api\Admin\AdminCenterController;
use App\Http\Controllers\Api\Admin\AdminCourseController;
use App\Http\Controllers\Api\Admin\AdminStatsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminVideoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CenterController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\StatsController;

// ====================================
// PUBLIC ROUTES (NO LOGIN REQUIRED)
// ====================================
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/test', fn() => response()->json(['message' => 'API IS WORKING!']));

// Categories & Centers (public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/count', [CategoryController::class, 'count']);
Route::get('/categories/{category}', [CategoryController::class, 'show']); // GET /api/categories/{id} (single)



Route::middleware('auth:sanctum')->get('centers/my', [CenterController::class, 'myCenters']);
Route::get('centers/count', [CenterController::class, 'count']);
Route::apiResource('centers', CenterController::class)->only(['index', 'show']);

// Courses (public index + show)
 Route::get('courses/without-videos', [CourseController::class, 'noVideos']);
Route::get('courses/count', [CategoryController::class, 'count']);
Route::apiResource('courses', CourseController::class)->only(['index', 'show']);

// COMMENTS: READ = PUBLIC, WRITE = PROTECTED
Route::get('comments', [CommentController::class, 'index']);        // ← Public: everyone sees
Route::get('likes', [LikeController::class, 'show']);               // ← Public
Route::get('shares/count', [ShareController::class, 'count']);      // ← Public
Route::get('ratings', [RatingController::class, 'show']);

// Auth (public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);


Route::get('/payment/callback', [PaymentApiController::class, 'callback']);

// ====================================
// PROTECTED ROUTES (REQUIRES LOGIN)
// ====================================
Route::middleware('auth:sanctum')->group(function () {
Route::post('categories', [CategoryController::class, 'store']);
Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
 Route::put('/me/profile', [AuthController::class, 'updateProfile']);
 Route::get('stats', [StatsController::class, 'index']);  
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me/enrolled-courses', [AuthController::class, 'enrolledCourses']);
    Route::get('/my-courses', [CourseController::class, 'enrolledCoursesByType']);

Route::get('/instructor/enrollments', [CourseController::class, 'myCourseEnrollments']);


    Route::middleware('auth:sanctum')->post('/payment/initialize', [PaymentApiController::class, 'initialize']);

        // Route::get('centers/my', [CenterController::class, 'myCenters']);


    // ONLY LOGGED-IN USERS CAN POST COMMENTS
    Route::post('comments', [CommentController::class, 'store']);     // ← PROTECTED

    // Likes & Shares (require login)
    Route::post('likes/toggle', [LikeController::class, 'toggle']);
    Route::post('shares', [ShareController::class, 'store']);
    Route::post('ratings/rate', [RatingController::class, 'rate']);
     Route::get('ratings/loginshow', [RatingController::class, 'loginshow']);

    // Admin routes...
    // Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])
    //  ->name('api.courses.edit');
    Route::put('/courses/{id}/update', [CourseController::class, 'update'])
     ->name('api.courses.update');
     

     Route::get('/courses/{id}/edit', [CourseController::class, 'edit'])
     ->name('api.courses.edit');
    Route::apiResource('courses', CourseController::class)->except(['index', 'show']);
    Route::apiResource('centers', CenterController::class)->except(['index', 'show', 'count']);
    Route::put('courses/{course}/toggle-publish', [CourseController::class, 'togglePublish']);
    Route::put('courses/{course}/publish', [CourseController::class, 'publish']);
    Route::get('courses/{course}/watch', [CourseController::class, 'watch']);
   Route::apiResource('videos', VideoController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('videos', [VideoController::class, 'store']);
    Route::put('videos/{video}/toggle-publish', [VideoController::class, 'togglePublish']);


});



// routes/api.php

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
Route::get('/courses', [AdminCourseController::class, 'index']);    
// Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::put('/users/{user}/toggle', [AdminUserController::class, 'toggleStatus']);
     Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    
    // Centers
    Route::get('/centers', [AdminCenterController::class, 'index']);
    Route::delete('/centers/{center}', [AdminCenterController::class, 'destroy']);
    Route::put('/centers/{center}/toggle', [AdminCenterController::class, 'toggleStatus']);
    
    // Courses
    
    Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']);
    Route::put('courses/{course}/toggle-active', [AdminCourseController::class, 'toggleActive']);

    // Videos
    Route::get('/videos', [AdminVideoController::class, 'index']);
    Route::delete('/videos/{video}', [AdminVideoController::class, 'destroy']);

    Route::put('videos/{video}/toggle-active', [AdminVideoController::class, 'toggleActive']);
   
    Route::get('/stats', [AdminStatsController::class, 'index']);
});

