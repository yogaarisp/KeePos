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

Route::get('/{any}', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return file_get_contents($indexPath);
    }
    
    return response()->json([
        'message' => 'API Endpoint not found or Frontend not built. Please run npm run build.',
        'status' => 'not_found'
    ], 404);
})->where('any', '.*');
