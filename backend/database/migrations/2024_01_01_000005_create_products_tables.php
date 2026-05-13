<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // POS Categories (Kategori Kasir)
        Schema::create('categories_product', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->foreignId('category_id')->constrained('categories_product')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('is_available');
        });

        // Custom Categories for Products (e.g., "Level Pedas", "Topping")
        Schema::create('custom_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['single', 'multiple'])->default('single');
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        // Custom Options (e.g., "Pedas Level 1", "Extra Cheese")
        Schema::create('custom_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_category_id')->constrained('custom_categories')->onDelete('cascade');
            $table->string('name', 100);
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pivot: Product <-> Custom Category
        Schema::create('product_custom_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('custom_category_id')->constrained('custom_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_id', 'custom_category_id'], 'pcc_unique');
        });

        // Tables (for Dine-in)
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number', 20)->unique();
            $table->integer('capacity')->default(4);
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
        Schema::dropIfExists('product_custom_categories');
        Schema::dropIfExists('custom_options');
        Schema::dropIfExists('custom_categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories_product');
    }
};
