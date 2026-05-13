<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is only for basic web-entry points. 
| All application logic is now handled in api.php for the SPA.
|
*/

Route::get('/', function () {
    return response()->json([
        'app' => 'Kee POS API',
        'version' => '1.0',
        'env' => app()->environment(),
        'documentation' => 'https://keetech.my.id/docs'
    ]);
});

// Fallback for SPA Routing (Handled by Nginx for production, or as last resort here)
Route::fallback(function () {
    return response()->json([
        'message' => 'API Endpoint not found. Please check your URL.',
        'status' => 'not_found'
    ], 404);
});
