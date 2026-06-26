<?php

use App\Http\Controllers\Api\DocumentationController;
use Illuminate\Support\Facades\Route;

if (config('api.documentation.enabled')) {
    Route::get(config('api.documentation.path', 'docs'), [DocumentationController::class, 'ui']);
    Route::get(config('api.documentation.json_path', 'docs/openapi.json'), [DocumentationController::class, 'json']);
    Route::get(config('api.documentation.yaml_path', 'docs/openapi.yaml'), [DocumentationController::class, 'yaml']);
}
