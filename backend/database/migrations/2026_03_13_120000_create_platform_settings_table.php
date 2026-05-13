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
        // Platform Settings (Superadmin Only)
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('type', 30)->default('text'); // text, number, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('group');
        });
        
        // Migrate existing platform settings (tenant_id = NULL) to platform_settings
        $this->migratePlatformSettings();
    }

    /**
     * Migrate data from settings where tenant_id is NULL
     */
    protected function migratePlatformSettings()
    {
        $platformSettings = DB::table('settings')
            ->whereNull('tenant_id')
            ->get();
        
        foreach ($platformSettings as $setting) {
            DB::table('platform_settings')->insert([
                'key' => $setting->key,
                'value' => $setting->value,
                'group' => $setting->group ?? 'general',
                'type' => $setting->type ?? 'text',
                'created_at' => $setting->created_at ?? now(),
                'updated_at' => $setting->updated_at ?? now(),
            ]);
        }
        
        // Delete migrated data from settings
        DB::table('settings')->whereNull('tenant_id')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
