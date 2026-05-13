<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Warehouse Stock
        Schema::table('stock_gudang', function (Blueprint $table) {
            $table->decimal('stock', 12, 4)->change();
            $table->decimal('min_stock', 12, 4)->change();
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('stock_before', 12, 4)->change();
            $table->decimal('stock_after', 12, 4)->change();
        });

        // Kitchen Stock
        Schema::table('stock_dapur', function (Blueprint $table) {
            $table->decimal('stock', 12, 4)->change();
            $table->decimal('min_stock', 12, 4)->change();
        });

        Schema::table('stock_dapur_transactions', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('stock_before', 12, 4)->change();
            $table->decimal('stock_after', 12, 4)->change();
        });
    }

    public function down(): void
    {
        // Revert to 2 decimal places if needed
        Schema::table('stock_gudang', function (Blueprint $table) {
            $table->decimal('stock', 12, 2)->change();
            $table->decimal('min_stock', 12, 2)->change();
        });
        
        Schema::table('stock_dapur', function (Blueprint $table) {
            $table->decimal('stock', 12, 2)->change();
            $table->decimal('min_stock', 12, 2)->change();
        });
    }
};
