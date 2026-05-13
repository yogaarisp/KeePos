<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * List of tables that need multi-tenancy.
     */
    protected $tables = [
        'users',
        'categories',
        'units',
        'stock_gudang',
        'unit_conversions',
        'stock_transactions',
        'stock_dapur',
        'stock_dapur_transactions',
        'categories_product',
        'products',
        'custom_categories',
        'custom_options',
        'product_custom_categories',
        'tables',
        'shifts',
        'shift_transactions',
        'sales',
        'order_items',
        'order_customizations',
        'recipes',
        'recipe_items',
        'production_recipes',
        'production_transactions',
        'waste_reports',
        'settings',
        'sale_missing_recipes',
        'payment_methods',
        'kitchen_unit_conversions'
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Kita pakai constrained ke tabel tenants yang baru dibuat
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->onDelete('cascade');
                    $table->index('tenant_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
