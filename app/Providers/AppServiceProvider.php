<?php
namespace App\Providers;

use App\Models\Course;
use App\Models\Video;
use App\Models\Center;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Allow Admin to find blocked Courses via ID in the URL
        Route::bind('courses', function ($value) {
            return Course::withoutGlobalScope('active')->findOrFail($value);
        });
        // 1. Allow Admin to find blocked Courses via ID in the URL
        Route::bind('users', function ($value) {
            return User::withoutGlobalScope('active')->findOrFail($value);
        });

        // 2. Allow Admin to find blocked Videos via ID in the URL
        Route::bind('videos', function ($value) {
            return Video::withoutGlobalScope('active')->findOrFail($value);
        });

        // 3. Allow Admin to find blocked Centers via ID in the URL
        Route::bind('centers', function ($value) {
            return Center::withoutGlobalScope('active')->findOrFail($value);
        });
    }
}