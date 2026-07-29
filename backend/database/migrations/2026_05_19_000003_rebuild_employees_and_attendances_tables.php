<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing tables if they exist to avoid collision
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');

        // Recreate employees table with full schema
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('position')->nullable();   // Jabatan
            $table->string('department')->nullable();
            $table->date('join_date')->nullable();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Recreate attendances table with full schema
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'leave', 'holiday'])->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'date'], 'attendance_unique');
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
    }
};
