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
        Schema::create('tenant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            
            // Shop Information
            $table->string('shop_name')->nullable();
            $table->string('shop_logo')->nullable();
            $table->string('shop_favicon')->nullable();
            $table->string('shop_tagline')->nullable();
            $table->text('shop_address')->nullable();
            $table->string('shop_phone', 50)->nullable();
            $table->string('shop_email', 100)->nullable();
            
            // Business Details
            $table->json('business_hours')->nullable(); // {"monday": "08:00-22:00", ...}
            $table->json('social_media')->nullable(); // {"instagram": "@shop", "facebook": "..."}
            
            // Branding
            $table->string('primary_color', 7)->default('#3B82F6'); // Hex color
            $table->string('secondary_color', 7)->default('#10B981');
            
            // Receipt Settings
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->boolean('show_logo_on_receipt')->default(true);
            
            $table->timestamps();
            
            $table->index('tenant_id');
        });
        
        // Migrate existing settings data to tenant_profiles
        $this->migrateExistingData();
    }

    /**
     * Migrate data from settings table to tenant_profiles
     */
    protected function migrateExistingData()
    {
        $tenants = \App\Models\Tenant::all();
        
        foreach ($tenants as $tenant) {
            // Get existing settings for this tenant
            $shopName = \App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'shop_name')
                ->value('value');
                
            $shopLogo = \App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'shop_logo')
                ->value('value');
                
            $shopTagline = \App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'shop_tagline')
                ->value('value');
                
            $shopAddress = \App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'shop_address')
                ->value('value');
                
            $shopPhone = \App\Models\Setting::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'shop_phone')
                ->value('value');
            
            // Create tenant profile
            DB::table('tenant_profiles')->insert([
                'tenant_id' => $tenant->id,
                'shop_name' => $shopName ?: $tenant->name,
                'shop_logo' => $shopLogo,
                'shop_tagline' => $shopTagline ?: 'Smart POS System',
                'shop_address' => $shopAddress ?: $tenant->address,
                'shop_phone' => $shopPhone ?: $tenant->phone,
                'shop_email' => $tenant->email,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_profiles');
    }
};
