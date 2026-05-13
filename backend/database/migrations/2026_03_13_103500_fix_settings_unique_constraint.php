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
        Schema::table('settings', function (Blueprint $table) {
            // Drop old unique constraint on 'key' only
            $table->dropUnique(['key']);
            
            // Add composite unique constraint on tenant_id + key
            // Ini memungkinkan setiap tenant punya key yang sama (misal: shop_name)
            // tapi kombinasi tenant_id + key harus unik
            $table->unique(['tenant_id', 'key'], 'settings_tenant_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_tenant_key_unique');
            $table->unique('key');
        });
    }
};

