# 🚀 Laravel Migration Plan - Resto POS System

## 📋 Overview

Dokumen ini berisi planning lengkap untuk migrasi sistem Resto POS dari **PHP Native** ke **Laravel Framework**.

---

## 🎯 Tujuan Migrasi

### Keuntungan Migrasi ke Laravel:
1. **Better Code Organization** - MVC pattern yang lebih terstruktur
2. **Built-in Security** - CSRF protection, SQL injection prevention
3. **ORM (Eloquent)** - Database operations lebih mudah dan aman
4. **Authentication System** - Built-in auth dengan middleware
5. **API Development** - RESTful API untuk mobile app
6. **Testing** - PHPUnit integration untuk automated testing
7. **Package Management** - Composer ecosystem yang luas
8. **Scalability** - Lebih mudah untuk scale up
9. **Modern Development** - Blade templating, artisan commands
10. **Community Support** - Dokumentasi dan community yang besar

---

## 📊 Current System Analysis

### Fitur yang Ada:

#### 1. **Management Gudang (Warehouse)**
- CRUD stok gudang
- Kategori bahan
- Unit conversion
- Stock transactions (in/out)
- Low stock alerts
- Stock reports

#### 2. **Management Dapur (Kitchen)**
- CRUD stok dapur
- Transfer stok dari gudang
- Konversi stok dapur
- Konsumsi stok
- Kitchen stock transactions
- Kitchen stock reports

#### 3. **POS (Point of Sale)**
- Product catalog dengan kategori
- Custom categories & options
- Shopping cart
- Multiple payment methods (Cash, Card, QRIS)
- Print receipt (thermal & regular)
- Cash drawer integration
- Order management
- Table management (Dine-in)
#### 5. **Resep (Recipes)**
- Order history & search
- Reprint receipt

#### 4. **Orders Management** ⭐ (Pesanan Diproses)
- View all processed orders
- Order details with items

#### 6. **Reports (Laporan)**
- Order search
- Reprint order receipt
- Order status (pending, completed, cancelled)
- Table assignment for dine-in orders
- Payment method tracking
- Order customizations display
- Real-time order updates

#### 7. **Shift Management**
- CRUD resep makanan/minuman
- Recipe items (komposisi bahan)
- HPP calculation (Harga Pokok Produksi)
- Production recipes (bahan setengah jadi)
- Recipe profitability analysis

#### 8. **User Management****
- Stock reports (gudang & dapur)
- Sales reports
- HPP reports
- Production reports
- Incoming/outgoing stock reports

#### 9. **Settings (Pengaturan Toko)**
- Export to PDF & Excel

#### 10. **Waste Reports**out (kasir only)
- Shift summary
- Print shift report (thermal & regular)

#### 11. **Android APK Integration**
- Role-based access (admin, kasir)
- User authentication
- Session management

#### 8. **Settings (Pengaturan Toko)**
- Shop information
- Printer settings
- System configuration
- Database backup

#### 9. **Waste Reports**
- CRUD waste reports
- Kitchen & warehouse waste
- Stock deduction on waste

#### 10. **Android APK Integration**
- WebView-based APK
- Bluetooth printer integration ESCPOS  
- Cash drawer control
│   │   │   ├── POS/
│   │   │   ├── Recipe/
│   │   │   ├── Report/
│   │   │   ├── Order/
│   │   │   ├── Shift/
## 🏗️ Laravel Project Structure

```
Wartegkee/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── StokGudang.php
│   │   ├── KitchenStock.php
│   │   ├── Product.php
│   │   ├── Recipe.php
│   │   ├── Sale.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Shift.php
│   │   └── ... Recipe/
│   │   │   ├── Report/
│   │   │   ├── Shift/
│   │   │   ├── User/
│   │   │   ├── Setting/
│   │   │   └── Waste/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── StokGudang.php
│   │   ├── KitchenStock.php
│   │   ├── Product.php
│   │   ├── Recipe.php
│   │   ├── Sale.php
│   │   ├── Shift.php
│   │   └── ...
│   │   ├── pos/
│   │   ├── recipes/
│   │   ├── reports/
│   │   ├── orders/
│   │   ├── shifts/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── auth/
│   │   ├── warehouse/
│   │   ├── kitchen/
│   │   ├── pos/
│   │   ├── recipes/
│   │   ├── reports/
│   │   ├── shifts/
│   │   ├── users/
│   │   ├── settings/
│   │   └── waste/
│   ├── lang/
│   └── js/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── storage/
├── tests/
└── vendor/
```

---

## 📦 Database Migration Strategy

### Phase 1: Create Migrations

```bash
# Users & Authentication
php artisan make:migration create_users_table
php artisan make:migration create_password_resets_table

# Warehouse Management
php artisan make:migration create_categories_table
php artisan make:migration create_stok_gudang_table
php artisan make:migration create_units_table
php artisan make:migration create_unit_conversion_table
# Sales & Orders
php artisan make:migration create_sales_transactions_table
php artisan make:migration create_orders_table
php artisan make:migration create_order_items_table
php artisan make:migration create_order_customizations_table
php artisan make:migration create_tables_table  # For dine-in table managementble
php artisan make:migration create_kitchen_stock_transactions_table

# Products & POS
php artisan make:migration create_categories_kasir_table
php artisan make:migration create_products_table
php artisan make:migration create_custom_categories_product_table
php artisan make:migration create_custom_options_table
php artisan make:migration create_product_custom_categories_table

# Recipes
php artisan make:migration create_recipes_table
php artisan make:migration create_recipe_items_table
php artisan make:migration create_production_recipe_table
php artisan make:migration create_production_transactions_table

# Sales & Orders
php artisan make:migration create_sales_transactions_table
php artisan make:migration create_orders_table
php artisan make:migration create_order_items_table
php artisan make:migration create_order_customizations_table

# Shifts
php artisan make:migration create_shifts_table
php artisan make:migration create_shift_transactions_table

# Waste Reports
php artisan make:migration create_waste_reports_table

# Settings
php artisan make:migration create_settings_table
```

### Phase 2: Define Eloquent Models

```php
// Example: StokGudang Model
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    protected $table = 'stok_gudang';
    
    protected $fillable = [
        'name', 'category_id', 'stock', 'unit', 
        'price_per_unit', 'min_stock'
    ];
    
    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'item_id');
    }
    
    public function unitConversions()
    {
        return $this->hasMany(UnitConversion::class, 'item_id');
    }
    
    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= min_stock');
    }
    
    // Accessors
    public function getTotalValueAttribute()
    {
        return $this->stock * $this->price_per_unit;
    }
}
```

---

## 🔐 Authentication & Authorization

### Laravel Breeze/Jetstream Setup

```bash
# Install Laravel Breeze (lightweight)
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run dev
php artisan migrate

# Or Laravel Jetstream (full-featured)
composer require laravel/jetstream
php artisan jetstream:install livewire
npm install && npm run dev
php artisan migrate
```

### Role-Based Middleware

```php
// app/Http/Middleware/CheckRole.php
namespace App\Http\Middleware;

class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, $roles)) {
            abort(403, 'Unauthorized action.');
        }
        
        return $next($request);
    }
}

// Register in Kernel.php
protected $routeMiddleware = [
    'role' => \App\Http\Middleware\CheckRole::class,
];

// Usage in routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
});
```

---

## 🎨 Frontend Strategy

### Option 1: Blade + Tailwind CSS 
- Keep current Tailwind CSS design
- Use Blade components for reusability
- Alpine.js for interactivity
- Livewire for reactive components (optional)

### Option 2: Vue.js/React SPA
- Full SPA with Laravel API backend
- Better for complex interactions
- More modern development experience

### Recommended: Hybrid Approach (Recommended)
- Blade + Tailwind for most pages
- Vue.js components for complex features (POS, Reports)
- API endpoints for mobile app

---

## 📱 API Development for Mobile App

### RESTful API Structure

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    
    Route::middleware('auth:sanctum')->group(function () {
        // POS
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
        
        // Shifts
        Route::post('/shifts/open', [ShiftController::class, 'open']);
        Route::post('/shifts/close', [ShiftController::class, 'close']);
        Route::get('/shifts/active', [ShiftController::class, 'active']);
        
        // Printer
        Route::post('/print/receipt', [PrintController::class, 'receipt']);
        
        // Stock
        Route::get('/warehouse/stock', [WarehouseController::class, 'index']);
        Route::get('/kitchen/stock', [KitchenController::class, 'index']);
    });
});
```

### API Response Format

```php
// app/Http/Controllers/API/BaseController.php
namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    public function sendResponse($result, $message, $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => $message,
        ], $code);
    }
    
    public function sendError($error, $errorMessages = [], $code = 404): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $error,
            'errors' => $errorMessages,
        ], $code);
    }
}
```
### Phase 3: Products & POS (Week 5-6)
- [ ] Product management
- [ ] Category management
- [ ] Custom categories
- [ ] POS interface
- [ ] Shopping cart
- [ ] Order processing
- [ ] Payment methods
- [ ] Table management (dine-in)

### Phase 3.5: Orders Management (Week 6)
- [ ] Orders listing page
- [ ] Order details view
- [ ] Order filtering & search
- [ ] Order status management
- [ ] Reprint receipt functionality
- [ ] Order history trackingmigrations
- [ ] Create Eloquent models
- [ ] Setup authentication
- [ ] Create base layouts (Blade)
- [ ] Migrate user management

### Phase 2: Warehouse & Kitchen (Week 3-4)
- [ ] Warehouse CRUD
- [ ] Kitchen CRUD
- [ ] Stock transactions
- [ ] Unit conversions
- [ ] Category management
- [ ] Stock reports

### Phase 3: Products & POS (Week 5-6)
- [ ] Product management
- [ ] Category management
- [ ] Custom categories
- [ ] POS interface
- [ ] Shopping cart
- [ ] Order processing
- [ ] Payment methods

### Phase 4: Recipes & Production (Week 7)
- [ ] Recipe management
- [ ] Recipe items
- [ ] HPP calculation
- [ ] Production recipes
- [ ] Production transactions

### Phase 5: Reports & Analytics (Week 8)
- [ ] Sales reports
- [ ] Stock reports
- [ ] HPP reports
- [ ] Production reports
- [ ] Export to PDF/Excel
- [ ] Dashboard analytics

### Phase 6: Shifts & Advanced Features (Week 9)
- [ ] Shift management
- [ ] Auto-close shift
- [ ] Shift reports
- [ ] Waste reports
- [ ] Settings management

### Phase 7: API & Mobile Integration (Week 10)
- [ ] RESTful API
- [ ] API authentication (Sanctum)
- [ ] API documentation
- [ ] Mobile app integration
- [ ] Printer integration API

### Phase 8: Testing & Deployment (Week 11-12)
- [ ] Unit tests
- [ ] Feature tests
- [ ] Performance optimization
- [ ] Security audit
- [ ] Deployment setup
- [ ] Documentation

---

## 📦 Required Laravel Packages

### Core Packages
```bash
# Authentication
composer require laravel/breeze
# or
composer require laravel/jetstream

# API Authentication
composer require laravel/sanctum

# PDF Generation
composer require barryvdh/laravel-dompdf

# Excel Export/Import
composer require maatwebsite/excel

# Image Processing
composer require intervention/image

# Activity Log
composer require spatie/laravel-activitylog

# Permission Management
composer require spatie/laravel-permission

# Backup
composer require spatie/laravel-backup

# API Documentation
composer require darkaonline/l5-swagger
```

### Frontend Packages
```bash
npm install alpinejs
npm install @tailwindcss/forms
npm install @tailwindcss/typography
npm install chart.js
npm install sweetalert2
npm install lucide
```

---

## 🗄️ Database Schema Comparison

### Current (PHP Native) vs Laravel (Eloquent)

#### Users Table
```php
// Current: Manual SQL
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    full_name VARCHAR(100),
    role ENUM('admin', 'kasir'),
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

// Laravel Migration
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('username', 50)->unique();
    $table->string('email', 100)->unique();
    $table->string('password');
    $table->string('full_name', 100);
    $table->enum('role', ['admin', 'kasir'])->default('kasir');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes(); // Optional: for soft delete
});
```

---

## 🔧 Service Layer Pattern

### Example: StockService

```php
// app/Services/StockService.php
namespace App\Services;

use App\Models\StokGudang;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStock($itemId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $quantity, $notes) {
            $item = StokGudang::findOrFail($itemId);
            
            // Update stock
            $item->increment('stock', $quantity);
            
            // Log transaction
            StockTransaction::create([
                'item_id' => $itemId,
                'type' => 'in',
                'quantity' => $quantity,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);
            
            return $item;
        });
    }
    
    public function reduceStock($itemId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $quantity, $notes) {
            $item = StokGudang::findOrFail($itemId);
            
            if ($item->stock < $quantity) {
                throw new \Exception('Stok tidak mencukupi');
            }
            
            // Update stock
            $item->decrement('stock', $quantity);
            
            // Log transaction
            StockTransaction::create([
                'item_id' => $itemId,
                'type' => 'out',
                'quantity' => $quantity,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);
            
            return $item;
        });
    }
    
    public function getLowStockItems()
    {
        return StokGudang::lowStock()->get();
    }
}
```

### Controller Usage

```php
// app/Http/Controllers/WarehouseController.php
namespace App\Http\Controllers;

use App\Services\StockService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected $stockService;
    
    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }
    
    public function addStock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $item = $this->stockService->addStock(
                $id, 
                $request->quantity, 
                $request->notes
            );
            
            return redirect()->back()->with('success', 'Stok berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
```

---

## 🎯 Key Features Implementation

### 1. POS System

```php
// app/Http/Controllers/POSController.php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Services\POSService;

class POSController extends Controller
{
    protected $posService;
    
    public function __construct(POSService $posService)
    {
        $this->posService = $posService;
    }
    
    public function index()
    {
        $products = Product::with('category')
            ->where('is_available', true)
            ->get();
            
        return view('pos.index', compact('products'));
    }
    
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.customizations' => 'nullable|array',
            'payment_method' => 'required|in:cash,card,qris',
            'payment_amount' => 'required|numeric|min:0',
        ]);
        
        try {
            $order = $this->posService->processOrder($validated);
            
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'message' => 'Order berhasil diproses',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### 2. HPP Calculation

```php
// app/Services/RecipeService.php
namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeItem;

class RecipeService
{
    public function calculateHPP($recipeId)
    {
        $recipe = Recipe::with('items.ingredient')->findOrFail($recipeId);
        
        $totalCost = 0;
        
        foreach ($recipe->items as $item) {
            $ingredient = $item->ingredient;
            $quantity = $item->quantity;
            
            // Convert to base unit if needed
            $baseQuantity = $this->convertToBaseUnit(
                $quantity, 
                $item->unit, 
                $ingredient->unit
            );
            
            $cost = $baseQuantity * $ingredient->price_per_unit;
            $totalCost += $cost;
        }
        
        // Update recipe HPP
        $recipe->update(['hpp' => $totalCost]);
        
        return $totalCost;
    }
    
    public function calculateProfitability($recipeId)
    {
        $recipe = Recipe::findOrFail($recipeId);
        
        $hpp = $recipe->hpp;
        $sellingPrice = $recipe->selling_price;
        
        $profit = $sellingPrice - $hpp;
        $margin = ($profit / $sellingPrice) * 100;
        $roi = ($profit / $hpp) * 100;
        
        return [
            'hpp' => $hpp,
            'selling_price' => $sellingPrice,
            'profit' => $profit,
            'margin' => round($margin, 2),
            'roi' => round($roi, 2),
        ];
    }
}
```

### 3. Shift Management

```php
// app/Services/ShiftService.php
namespace App\Services;

use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function openShift($userId, $initialCash)
    {
        // Check if user already has active shift
        $activeShift = Shift::where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();
            
        if ($activeShift) {
            throw new \Exception('Anda sudah memiliki shift aktif');
        }
        
        return Shift::create([
            'user_id' => $userId,
            'opened_at' => now(),
            'initial_cash' => $initialCash,
            'expected_cash' => $initialCash,
        ]);
    }
    
    public function closeShift($shiftId, $actualCash, $notes = null)
    {
        return DB::transaction(function () use ($shiftId, $actualCash, $notes) {
            $shift = Shift::findOrFail($shiftId);
            
            // Calculate expected cash from sales
            $totalSales = $shift->sales()->sum('total_amount');
            $expectedCash = $shift->initial_cash + $totalSales;
            
            $variance = $actualCash - $expectedCash;
            
            $shift->update([
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'variance' => $variance,
                'notes' => $notes,
            ]);
            
            return $shift;
        });
    }
    
    public function getActiveShift($userId)
    {
        return Shift::where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();
    }
}
```

### 4. Report Generation

```php
// app/Services/ReportService.php
namespace App\Services;

use App\Models\Sale;
use App\Models\StokGudang;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    public function generateSalesReport($startDate, $endDate, $format = 'pdf')
    {
        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->with('items.product')
            ->get();
            
        $data = [
            'sales' => $sales,
            'total' => $sales->sum('total_amount'),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.sales-pdf', $data);
            return $pdf->download('sales-report.pdf');
        } else {
            return Excel::download(
                new SalesExport($sales), 
                'sales-report.xlsx'
            );
        }
    }
    
    public function generateStockReport($format = 'pdf')
    {
        $stocks = StokGudang::with('category')->get();
        
        $data = [
            'stocks' => $stocks,
            'total_value' => $stocks->sum('total_value'),
            'low_stock_count' => $stocks->where('stock', '<=', 'min_stock')->count(),
        ];
        
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.stock-pdf', $data);
            return $pdf->download('stock-report.pdf');
        } else {
            return Excel::download(
                new StockExport($stocks), 
                'stock-report.xlsx'
            );
        }
    }
}
```

---

## 🧪 Testing Strategy

### Unit Tests Example

```php
// tests/Unit/StockServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\StockService;
use App\Models\StokGudang;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected $stockService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = new StockService();
    }
    
    public function test_can_add_stock()
    {
        $item = StokGudang::factory()->create(['stock' => 100]);
        
        $this->stockService->addStock($item->id, 50);
        
        $this->assertEquals(150, $item->fresh()->stock);
    }
    
    public function test_cannot_reduce_stock_below_zero()
    {
        $item = StokGudang::factory()->create(['stock' => 10]);
        
        $this->expectException(\Exception::class);
        
        $this->stockService->reduceStock($item->id, 20);
    }
}
```

### Feature Tests Example

```php
// tests/Feature/POSTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class POSTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_kasir_can_access_pos()
    {
        $user = User::factory()->create(['role' => 'kasir']);
        
        $response = $this->actingAs($user)->get('/pos');
        
        $response->assertStatus(200);
    }
    
    public function test_can_checkout_order()
    {
        $user = User::factory()->create(['role' => 'kasir']);
        $product = Product::factory()->create(['price' => 10000]);
        
        $response = $this->actingAs($user)->postJson('/pos/checkout', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ],
            'payment_method' => 'cash',
            'payment_amount' => 20000,
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
```

---

## 🚀 Deployment Strategy

### Server Requirements
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js & NPM
- Nginx/Apache
- SSL Certificate

### Deployment Steps

```bash
# 1. Clone repository
git clone https://github.com/your-repo/resto-laravel.git
cd resto-laravel

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Database setup
php artisan migrate --force
php artisan db:seed --force

# 5. Storage & Cache
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. Queue worker (optional)
php artisan queue:work --daemon
```

---

## 📊 Performance Optimization

### Database Optimization
```php
// Use eager loading
$products = Product::with(['category', 'customCategories'])->get();

// Use select to limit columns
$products = Product::select('id', 'name', 'price')->get();

// Use chunk for large datasets
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process product
    }
});

// Use database indexing
Schema::table('products', function (Blueprint $table) {
    $table->index('category_id');
    $table->index('is_available');
});
```

### Caching Strategy
```php
// Cache configuration
'cache' => [
    'default' => env('CACHE_DRIVER', 'redis'),
],

// Cache usage
$products = Cache::remember('products.all', 3600, function () {
    return Product::with('category')->get();
});

// Clear cache
Cache::forget('products.all');
php artisan cache:clear
```

### Queue Jobs
```php
// app/Jobs/ProcessOrder.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessOrder implements ShouldQueue
{
    use Queueable;
    
    protected $order;
    
    public function __construct($order)
    {
        $this->order = $order;
    }
    
    public function handle()
    {
        // Process order
        // Send notifications
        // Update inventory
    }
}

// Dispatch job
ProcessOrder::dispatch($order);
```

---

## 💰 Cost Estimation

### Development Time
- **Phase 1-2 (Setup & Core)**: 2 weeks
- **Phase 3-4 (Warehouse & Kitchen)**: 2 weeks
- **Phase 5-6 (POS & Products)**: 2 weeks
- **Phase 7 (Recipes)**: 1 week
- **Phase 8 (Reports)**: 1 week
- **Phase 9 (Shifts & Advanced)**: 1 week
- **Phase 10 (API & Mobile)**: 1 week
- **Phase 11-12 (Testing & Deployment)**: 2 weeks

**Total**: ~12 weeks (3 months)

### Team Requirements
- 1 Senior Laravel Developer
- 1 Frontend Developer (Vue.js/Blade)
- 1 QA Tester (part-time)
- 1 DevOps Engineer (part-time)

### Infrastructure Cost (Monthly)
- VPS Server (4GB RAM, 2 CPU): $20-40
- Database (MySQL): Included
- SSL Certificate: Free (Let's Encrypt)
- Backup Storage: $5-10
- CDN (optional): $10-20

**Total Monthly**: $35-70

---

## ⚠️ Risks & Mitigation

### Risk 1: Data Migration Issues
**Mitigation**: 
- Create comprehensive backup before migration
- Test migration on staging environment
- Use Laravel seeders for test data
- Implement rollback strategy

### Risk 2: Performance Degradation
**Mitigation**:
- Implement caching (Redis)
- Optimize database queries
- Use queue for heavy tasks
- Load testing before deployment

### Risk 3: Learning Curve
**Mitigation**:
- Provide Laravel training for team
- Create comprehensive documentation
- Use Laravel best practices
- Code review process

### Risk 4: Android APK Integration
**Mitigation**:
- Maintain API compatibility
- Version API endpoints
- Test thoroughly with APK
- Provide fallback mechanisms

---

## 🎓 Training & Documentation

### Developer Training Topics
1. Laravel Fundamentals
2. Eloquent ORM
3. Blade Templating
4. API Development
5. Testing with PHPUnit
6. Deployment & DevOps

### Documentation Requirements
1. API Documentation (Swagger)
2. Database Schema Documentation
3. User Manual
4. Developer Guide
5. Deployment Guide
6. Troubleshooting Guide

---

## 📈 Success Metrics

### Technical Metrics
- [ ] Page load time < 2 seconds
- [ ] API response time < 500ms
- [ ] Test coverage > 80%
- [ ] Zero critical bugs in production
- [ ] 99.9% uptime

### Business Metrics
- [ ] User satisfaction > 90%
- [ ] Transaction processing time reduced by 30%
- [ ] Report generation time reduced by 50%
- [ ] Mobile app adoption > 70%
- [ ] System maintenance time reduced by 40%

---

## 🔄 Backward Compatibility

### Data Migration Script
```php
// database/seeders/MigrateFromOldSystemSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateFromOldSystemSeeder extends Seeder
{
    public function run()
    {
        // Migrate users
        $oldUsers = DB::connection('old_db')->table('users')->get();
        foreach ($oldUsers as $user) {
            \App\Models\User::create([
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'password' => $user->password_hash,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ]);
        }
        
        // Migrate products
        $oldProducts = DB::connection('old_db')->table('products')->get();
        foreach ($oldProducts as $product) {
            \App\Models\Product::create([
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->sales_amount,
                'category_id' => $product->category_id,
                'is_available' => $product->is_available,
                'image' => $product->image,
                'created_at' => $product->created_at,
            ]);
        }
        
        // Continue for other tables...
    }
}
```

### API Versioning
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // Old API endpoints for backward compatibility
});

Route::prefix('v2')->group(function () {
    // New Laravel API endpoints
});
```

---

## 🎯 Conclusion

### Why Migrate to Laravel?

1. **Better Code Quality**: MVC pattern, PSR standards
2. **Faster Development**: Built-in features, packages
3. **Easier Maintenance**: Clear structure, documentation
4. **Better Security**: Built-in protection, regular updates
5. **Scalability**: Easy to scale, optimize
6. **Modern Stack**: Latest PHP features, tools
7. **Community Support**: Large community, resources
8. **Future-Proof**: Active development, long-term support

### Next Steps

1. **Review this document** with the team
2. **Get stakeholder approval** for migration
3. **Setup development environment**
4. **Start Phase 1** (Setup & Core)
5. **Regular progress reviews** (weekly)
6. **Testing at each phase**
7. **Gradual rollout** (staging → production)

---

## 📞 Support & Resources

### Laravel Resources
- Official Documentation: https://laravel.com/docs
- Laracasts: https://laracasts.com
- Laravel News: https://laravel-news.com
- Laravel Daily: https://laraveldaily.com

### Community
- Laravel Forum: https://laracasts.com/discuss
- Laravel Discord: https://discord.gg/laravel
- Stack Overflow: https://stackoverflow.com/questions/tagged/laravel

---

**Document Version**: 1.0  
**Created**: <?php echo date('Y-m-d'); ?>  
**Author**: Development Team  
**Status**: Planning Phase  

---

**Note**: This is a comprehensive migration plan. Adjust timeline and resources based on your team's capacity and project requirements.


---

## 📦 Orders Management Implementation

### Order Model & Relationships

```php
// app/Models/Order.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'sales'; // Using existing sales table
    
    protected $fillable = [
        'user_id',
        'table_id',
        'total_amount',
        'payment_method',
        'payment_amount',
        'change_amount',
        'status',
        'notes',
    ];
    
    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function table()
    {
        return $this->belongsTo(Table::class);
    }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'sale_id');
    }
    
    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
    
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    // Accessors
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }
    
    public function getReceiptNumberAttribute()
    {
        return 'ORD-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}

// app/Models/OrderItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
        'notes',
    ];
    
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class, 'sale_id');
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function customizations()
    {
        return $this->hasMany(OrderCustomization::class);
    }
}
```

### Orders Controller

```php
// app/Http/Controllers/OrderController.php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;
    
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    
    /**
     * Display orders list with filters
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'table', 'items.product'])
            ->where('total_amount', '>', 0);
        
        // Date filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange(
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            );
        } else {
            // Default to today
            $query->today();
        }
        
        // Status filter
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('orders.index', compact('orders'));
    }
    
    /**
     * Display order details
     */
    public function show($id)
    {
        $order = Order::with([
            'user',
            'table',
            'items.product',
            'items.customizations.option'
        ])->findOrFail($id);
        
        return view('orders.show', compact('order'));
    }
    
    /**
     * Get order details for AJAX
     */
    public function getDetails($id)
    {
        $order = Order::with([
            'user',
            'table',
            'items.product',
            'items.customizations.option'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }
    
    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);
        
        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);
        
        return redirect()->back()->with('success', 'Status order berhasil diupdate');
    }
    
    /**
     * Reprint receipt
     */
    public function reprint($id)
    {
        $order = Order::with([
            'user',
            'table',
            'items.product',
            'items.customizations.option'
        ])->findOrFail($id);
        
        return view('orders.receipt', compact('order'));
    }
    
    /**
     * Get receipt data for thermal print
     */
    public function getReceiptData($id)
    {
        $order = Order::with([
            'user',
            'table',
            'items.product',
            'items.customizations.option'
        ])->findOrFail($id);
        
        $receiptData = $this->orderService->formatReceiptData($order);
        
        return response()->json([
            'success' => true,
            'data' => $receiptData,
        ]);
    }
}
```

### Order Service

```php
// app/Services/OrderService.php
namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create new order from POS
     */
    public function createOrder($data)
    {
        return DB::transaction(function () use ($data) {
            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'table_id' => $data['table_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'payment_method' => $data['payment_method'],
                'payment_amount' => $data['payment_amount'],
                'change_amount' => $data['change_amount'],
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Create order items
            foreach ($data['items'] as $item) {
                $orderItem = OrderItem::create([
                    'sale_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'] ?? null,
                ]);
                
                // Create customizations if any
                if (isset($item['customizations'])) {
                    foreach ($item['customizations'] as $customization) {
                        $orderItem->customizations()->create([
                            'custom_option_id' => $customization['option_id'],
                            'price' => $customization['price'],
                        ]);
                    }
                }
            }
            
            // Update stock (if needed)
            $this->updateStockFromOrder($order);
            
            return $order;
        });
    }
    
    /**
     * Format receipt data for thermal printer
     */
    public function formatReceiptData($order)
    {
        $shopSettings = getSettings();
        
        $receiptData = [
            'shop_name' => $shopSettings['shop_name'] ?? 'Resto',
            'shop_address' => $shopSettings['shop_address'] ?? '',
            'shop_phone' => $shopSettings['shop_phone'] ?? '',
            'receipt_number' => $order->receipt_number,
            'date' => $order->formatted_date,
            'cashier' => $order->user->full_name,
            'table' => $order->table ? $order->table->table_number : '-',
            'items' => [],
            'subtotal' => $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_amount' => $order->payment_amount,
            'change' => $order->change_amount,
        ];
        
        foreach ($order->items as $item) {
            $itemData = [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
                'customizations' => [],
            ];
            
            foreach ($item->customizations as $custom) {
                $itemData['customizations'][] = [
                    'name' => $custom->option->name,
                    'price' => $custom->price,
                ];
            }
            
            $receiptData['items'][] = $itemData;
        }
        
        return $receiptData;
    }
    
    /**
     * Update stock from order
     */
    protected function updateStockFromOrder($order)
    {
        // Implementation depends on your stock management logic
        // This is where you reduce kitchen stock based on recipes
    }
}
```

### Routes

```php
// routes/web.php
Route::middleware(['auth', 'role:admin,kasir'])->group(function () {
    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/details', [OrderController::class, 'getDetails'])->name('orders.details');
    Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('/orders/{id}/reprint', [OrderController::class, 'reprint'])->name('orders.reprint');
    Route::get('/orders/{id}/receipt-data', [OrderController::class, 'getReceiptData'])->name('orders.receipt-data');
});
```

### Blade View Example

```blade
{{-- resources/views/orders/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Pesanan Diproses</h1>
    </div>
    
    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ request('start_date', date('Y-m-d')) }}" 
                       class="w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}" 
                       class="w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300">
                    <option value="">Semua</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
            </div>
        </form>
    </div>
    
    {{-- Orders Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">Order ID</th>
                    <th class="px-6 py-3 text-left">Tanggal</th>
                    <th class="px-6 py-3 text-left">Kasir</th>
                    <th class="px-6 py-3 text-left">Meja</th>
                    <th class="px-6 py-3 text-right">Total</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $order->receipt_number }}</td>
                    <td class="px-6 py-4">{{ $order->formatted_date }}</td>
                    <td class="px-6 py-4">{{ $order->user->full_name }}</td>
                    <td class="px-6 py-4">{{ $order->table->table_number ?? '-' }}</td>
                    <td class="px-6 py-4 text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($order->status == 'completed') bg-green-100 text-green-800
                            @elseif($order->status == 'pending') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="viewOrder({{ $order->id }})" 
                                class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="reprintReceipt({{ $order->id }})" 
                                class="text-orange-600 hover:text-orange-800">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- Pagination --}}
    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
@endsection
```

---

## 📝 Summary: Orders Module in Laravel

### Key Features Implemented:
1. ✅ Orders listing with pagination
2. ✅ Date range filtering
3. ✅ Status filtering
4. ✅ Search functionality
5. ✅ Order details view
6. ✅ Reprint receipt
7. ✅ Order status management
8. ✅ Table assignment tracking
9. ✅ Payment method tracking
10. ✅ Customizations display

### Database Tables:
- `sales` (orders)
- `order_items`
- `order_customizations`
- `tables`

### API Endpoints:
- `GET /orders` - List orders
- `GET /orders/{id}` - Order details
- `GET /orders/{id}/details` - AJAX order details
- `POST /orders/{id}/status` - Update status
- `GET /orders/{id}/reprint` - Reprint receipt
- `GET /orders/{id}/receipt-data` - Receipt data for thermal print

---
