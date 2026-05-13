<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kitchen unit conversion table
        if (!Schema::hasTable('kitchen_unit_conversions')) {
            Schema::create('kitchen_unit_conversions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kitchen_item_id')->constrained('stock_dapur')->onDelete('cascade');
                $table->string('base_unit', 50);
                $table->string('convert_to_unit', 50);
                $table->decimal('ratio', 12, 3)->default(1);
                $table->timestamps();

                $table->index('kitchen_item_id');
                $table->unique(['kitchen_item_id', 'base_unit', 'convert_to_unit'], 'unique_conversion');
            });
        }

        // Add cost_price to stock_dapur if not exists
        if (!Schema::hasColumn('stock_dapur', 'cost_price')) {
            Schema::table('stock_dapur', function (Blueprint $table) {
                $table->decimal('cost_price', 12, 2)->default(0)->after('unit');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_unit_conversions');

        if (Schema::hasColumn('stock_dapur', 'cost_price')) {
            Schema::table('stock_dapur', function (Blueprint $table) {
                $table->dropColumn('cost_price');
            });
        }
    }
};
