<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\GitConfigController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected authentication routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('profile', [AuthController::class, 'profile']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::get('ping', function () {
    return response()->json(['message' => 'pong']);
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {

    // Projects routes
    Route::apiResource('projects', ProjectController::class);
    Route::prefix('projects/{project}')->group(function () {
        Route::get('git-config', [GitConfigController::class, 'show']);

        // Deploys routes (nested under projects) - Read-only
        Route::get('deploys', [DeployController::class, 'index']);
    });

    // Git Config routes (standalone)
    Route::post('git-config', [GitConfigController::class, 'store']);
    Route::put('git-config', [GitConfigController::class, 'update']);

    Route::get('deploys/{deploy}', [DeployController::class, 'show']);

});
