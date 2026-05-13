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
        // Rename settings table to tenant_settings
        Schema::rename('settings', 'tenant_settings');
        
        // Make tenant_id NOT NULL (karena sekarang hanya untuk tenant)
        Schema::table('tenant_settings', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('settings_tenant_key_unique');
            
            // Make tenant_id NOT NULL
            $table->foreignId('tenant_id')->nullable(false)->change();
            
            // Add new unique constraint with new name
            $table->unique(['tenant_id', 'key'], 'tenant_settings_tenant_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropUnique('tenant_settings_tenant_key_unique');
            $table->foreignId('tenant_id')->nullable()->change();
            $table->unique(['tenant_id', 'key'], 'settings_tenant_key_unique');
        });
        
        Schema::rename('tenant_settings', 'settings');
    }
};
