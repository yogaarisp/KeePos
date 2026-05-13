<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warehouse Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Units
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('abbreviation', 20);
            $table->timestamps();
        });

        // Warehouse Stock (Stock Gudang)
        Schema::create('stock_gudang', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->decimal('stock', 12, 2)->default(0);
            $table->string('unit', 50);
            $table->decimal('price_per_unit', 15, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('stock');
        });

        // Unit Conversions
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('stock_gudang')->onDelete('cascade');
            $table->string('from_unit', 50);
            $table->string('to_unit', 50);
            $table->decimal('conversion_factor', 12, 4);
            $table->timestamps();
        });

        // Stock Transactions (Warehouse)
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('stock_gudang')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2)->default(0);
            $table->decimal('stock_after', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['item_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('unit_conversions');
        Schema::dropIfExists('stock_gudang');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');
    }
};
