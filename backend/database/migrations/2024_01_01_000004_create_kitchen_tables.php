<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kitchen Stock
        Schema::create('stock_dapur', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->decimal('stock', 12, 2)->default(0);
            $table->string('unit', 50);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
        });

        // Kitchen Stock Transactions
        Schema::create('stock_dapur_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_stock_id')->constrained('stock_dapur')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'transfer', 'conversion', 'consumption']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2)->default(0);
            $table->decimal('stock_after', 12, 2)->default(0);
            $table->foreignId('source_gudang_id')->nullable()->constrained('stock_gudang')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['kitchen_stock_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_dapur_transactions');
        Schema::dropIfExists('stock_dapur');
    }
};
