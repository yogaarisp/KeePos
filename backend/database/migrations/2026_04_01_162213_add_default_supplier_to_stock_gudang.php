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
        Schema::table('stock_gudang', function (Blueprint $table) {
            $table->foreignId('default_supplier_id')->nullable()->after('category_id')->constrained('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_gudang', function (Blueprint $table) {
            $table->dropForeign(['default_supplier_id']);
            $table->dropColumn('default_supplier_id');
        });
    }
};
