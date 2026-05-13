<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Waste Reports
        Schema::create('waste_reports', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50); // 'gudang' or 'kitchen'
            $table->unsignedBigInteger('source_id');
            $table->string('item_name', 150);
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 50);
            $table->decimal('estimated_loss', 15, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('created_at');
        });

        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('type', 30)->default('text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('waste_reports');
    }
};
