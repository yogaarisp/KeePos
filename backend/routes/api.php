<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\MaterialCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\InventoryReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\WasteController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\POSController;
use App\Http\Controllers\Api\Admin\TenantController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SupplierController;

Route::get('/', function () {
    return response()->json([
        'app' => 'Kee POS API',
        'version' => '1.0',
        'status' => 'OK',
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

// Password Reset Routes
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);

// Email Verification Routes
Route::post('/email/verify-otp', [\App\Http\Controllers\Api\VerificationController::class, 'verifyOTP']);
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Api\VerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [\App\Http\Controllers\Api\VerificationController::class, 'resend'])->middleware(['auth:sanctum'])->name('verification.resend');

Route::get('/settings/public', [SettingController::class, 'publicSettings']);
Route::get('/subscriptions/plans/public', [SubscriptionController::class, 'plans']);

Route::post('/subscriptions/webhook', [SubscriptionController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin/SaaS Management - No subscription check for superadmin
    Route::middleware('role:superadmin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [TenantController::class, 'dashboard']);
        Route::get('/tenants', [TenantController::class, 'index']);
        Route::get('/tenants/stats', [TenantController::class, 'stats']);
        Route::get('/invoices', [TenantController::class, 'globalInvoices']);
        Route::put('/tenants/{id}', [TenantController::class, 'update']);
        Route::delete('/tenants/{id}', [TenantController::class, 'destroy']);
        
        // Platform Config
        Route::get('/saas/config', [SettingController::class, 'getSaaSConfig']);
        Route::post('/saas/config', [SettingController::class, 'updateSaaSConfig']);
        Route::post('/saas/test-smtp', [SettingController::class, 'testSMTP']);
        Route::get('/saas/db/backup', [SettingController::class, 'backupFullDatabase']);
        Route::post('/saas/db/restore', [SettingController::class, 'restoreDatabase']);
        Route::get('/saas/system-info', [SettingController::class, 'systemInfo']);
        Route::post('/saas/cache/clear', [SettingController::class, 'clearCache']);
        Route::post('/saas/optimize', [SettingController::class, 'optimizeApp']);
    });

    // Subscription Routes - No subscription check needed for these
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('/subscriptions/status', [SubscriptionController::class, 'status']);
    Route::post('/subscriptions/checkout', [SubscriptionController::class, 'checkout']);
    Route::get('/subscriptions/invoices', [SubscriptionController::class, 'invoices']);

    // Tenant routes - Apply subscription check
    Route::middleware('subscription')->group(function () {
        // Order Routes
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        // Warehouse Routes - Required Basic
        Route::middleware('plan:basic')->group(function () {
            Route::get('/warehouse', [WarehouseController::class, 'index']);
            Route::post('/warehouse', [WarehouseController::class, 'store']);
            Route::put('/warehouse/{id}', [WarehouseController::class, 'update']);
            Route::delete('/warehouse/{id}', [WarehouseController::class, 'destroy']);
            Route::post('/warehouse/{id}/add-stock', [WarehouseController::class, 'addStock']);
            Route::post('/warehouse/{id}/reduce-stock', [WarehouseController::class, 'reduceStock']);
            Route::get('/warehouse/{id}/transactions', [WarehouseController::class, 'transactions']);
        });

        // Material Category Routes
        Route::get('/material-categories', [MaterialCategoryController::class, 'index']);
        Route::post('/material-categories', [MaterialCategoryController::class, 'store']);
        Route::put('/material-categories/{id}', [MaterialCategoryController::class, 'update']);
        Route::delete('/material-categories/{id}', [MaterialCategoryController::class, 'destroy']);


        // Product & Category Routes
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::get('/categories', [ProductController::class, 'categories']);
        Route::post('/categories', [ProductController::class, 'storeCategory']);
        Route::put('/categories/{id}', [ProductController::class, 'updateCategory']);
        Route::delete('/categories/{id}', [ProductController::class, 'destroyCategory']);

        // Table Routes
        Route::get('/tables', [TableController::class, 'index']);
        Route::post('/tables', [TableController::class, 'store']);
        Route::put('/tables/{id}', [TableController::class, 'update']);
        Route::delete('/tables/{id}', [TableController::class, 'destroy']);
        Route::patch('/tables/{id}/status', [TableController::class, 'updateStatus']);

        // Report Routes - Basic features
        Route::get('/reports/sales', [ReportController::class, 'salesSummary']);
        Route::middleware('plan:basic')->group(function () {
            Route::get('/reports/stock', [ReportController::class, 'stockSummary']);
            Route::get('/reports/inventory', [InventoryReportController::class, 'index']);
            Route::get('/reports/export-excel', [ReportController::class, 'exportExcel']);
        });

        // Setting Routes
        Route::get('/settings', [SettingController::class, 'index']);
        Route::get('/settings/billing-stats', [SettingController::class, 'getBillingStats']);
        Route::post('/settings', [SettingController::class, 'update']);
        Route::get('/settings/db/export', [SettingController::class, 'exportDatabase']);
        Route::post('/settings/db/import', [SettingController::class, 'importDatabase']);
        
        // Google Sheets Sync - Required Basic Plan
        Route::middleware('plan:basic')->group(function () {
            Route::post('/settings/sync-google-sheet', [SettingController::class, 'syncGoogleSheet']);
            Route::post('/settings/sync-inventory-gsheet', [SettingController::class, 'syncInventoryToGSheet']);
        });
        
        Route::post('/settings/payment-methods', [SettingController::class, 'storePaymentMethod']);
        Route::put('/settings/payment-methods/{id}', [SettingController::class, 'updatePaymentMethod']);
        Route::delete('/settings/payment-methods/{id}', [SettingController::class, 'destroyPaymentMethod']);

        // User Routes
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Supplier Routes - Required Pro
        Route::middleware('plan:pro')->group(function () {
            Route::get('/suppliers/stats', [SupplierController::class, 'stats']);
            Route::apiResource('suppliers', SupplierController::class);
        });

        // Kitchen Routes - Required Pro
        Route::middleware('plan:pro')->group(function () {
            Route::get('/kitchen', [KitchenController::class, 'index']);
            Route::post('/kitchen', [KitchenController::class, 'store']);
            Route::get('/kitchen/warehouse-items', [KitchenController::class, 'warehouseItems']);
            Route::post('/kitchen/transfer', [KitchenController::class, 'transfer']);
            Route::put('/kitchen/{id}', [KitchenController::class, 'update']);
            Route::delete('/kitchen/{id}', [KitchenController::class, 'destroy']);
            Route::post('/kitchen/{id}/consume', [KitchenController::class, 'consume']);
            Route::post('/kitchen/{id}/add-stock', [KitchenController::class, 'addStock']);
            Route::post('/kitchen/{id}/return', [KitchenController::class, 'returnToWarehouse']);
            Route::get('/kitchen/{id}/conversions', [KitchenController::class, 'getConversions']);
            Route::post('/kitchen/{id}/conversions', [KitchenController::class, 'addConversion']);
            Route::put('/kitchen/{id}/conversions/{conversionId}', [KitchenController::class, 'updateConversion']);
            Route::delete('/kitchen/{id}/conversions/{conversionId}', [KitchenController::class, 'deleteConversion']);
            Route::get('/kitchen/{id}/transactions', [KitchenController::class, 'transactions']);
        });


        // Unit Routes
        Route::get('/units', [UnitController::class, 'index']);
        Route::post('/units', [UnitController::class, 'store']);
        Route::put('/units/{id}', [UnitController::class, 'update']);
        Route::delete('/units/{id}', [UnitController::class, 'destroy']);

        // Waste Routes
        Route::get('/waste', [WasteController::class, 'index']);
        Route::post('/waste', [WasteController::class, 'store']);
        Route::delete('/waste/{id}', [WasteController::class, 'destroy']);

        // Shift Routes
        Route::get('/shifts', [ShiftController::class, 'index']);
        Route::get('/shifts/active', [ShiftController::class, 'active']);
        Route::get('/shifts/{id}/transactions', [ShiftController::class, 'transactions']);
        Route::post('/shifts', [ShiftController::class, 'store']);
        Route::post('/shifts/{id}/close', [ShiftController::class, 'close']);

        // Recipe Routes - Required Pro
        Route::middleware('plan:pro')->group(function () {
            Route::get('/recipes', [RecipeController::class, 'index']);
            Route::get('/recipes/ingredients', [RecipeController::class, 'ingredients']);
            Route::get('/recipes/{id}', [RecipeController::class, 'show']);
            Route::post('/recipes', [RecipeController::class, 'store']);
            Route::put('/recipes/{id}', [RecipeController::class, 'update']);
            Route::delete('/recipes/{id}', [RecipeController::class, 'destroy']);
            Route::get('/missing-recipes', [RecipeController::class, 'missingRecipes']);
            Route::delete('/missing-recipes/{productId}', [RecipeController::class, 'dismissMissingRecipe']);
        });

        // POS Routes
        Route::get('/pos/init', [POSController::class, 'init']);
        Route::post('/pos/checkout', [POSController::class, 'checkout']);
    });
});
