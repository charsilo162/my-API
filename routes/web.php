<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
Route::get('/', function () {
    return view('welcome');
});


Route::get('/reset-password/{token}', function (string $token, Request $request) {
    return response()->json([
        'token' => $token,
        'email' => $request->query('email'),
    ]);
})->name('password.reset');
