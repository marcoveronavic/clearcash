<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Queste rotte sono prefissate con /api e NON richiedono CSRF.
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check
Route::get('health', fn () => response()->json(['ok' => true]));
