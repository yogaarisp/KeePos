<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recipes
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->decimal('hpp', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->enum('type', ['product', 'production'])->default('product');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Recipe Items (Ingredients)
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->string('ingredient_type', 50); // 'gudang' or 'kitchen'
            $table->unsignedBigInteger('ingredient_id');
            $table->decimal('quantity', 12, 4);
            $table->string('unit', 50);
            $table->decimal('cost', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['ingredient_type', 'ingredient_id']);
        });

        // Production Recipes (Semi-finished goods)
        Schema::create('production_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->decimal('output_quantity', 12, 2);
            $table->string('output_unit', 50);
            $table->foreignId('output_kitchen_stock_id')->nullable()->constrained('stock_dapur')->onDelete('set null');
            $table->timestamps();
        });

        // Production Transactions
        Schema::create('production_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_recipe_id')->constrained('production_recipes')->onDelete('cascade');
            $table->decimal('quantity_produced', 12, 2);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_transactions');
        Schema::dropIfExists('production_recipes');
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('recipes');
    }
};
