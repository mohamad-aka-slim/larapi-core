<?php

use App\Http\Controllers\Api\DocumentationController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('v1/status', StatusController::class);

if (config('api.documentation.enabled')) {
    Route::get(config('api.documentation.path', 'docs'), [DocumentationController::class, 'ui']);
    Route::get(config('api.documentation.json_path', 'docs/openapi.json'), [DocumentationController::class, 'json']);
    Route::get(config('api.documentation.yaml_path', 'docs/openapi.yaml'), [DocumentationController::class, 'yaml']);
}

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });
});
