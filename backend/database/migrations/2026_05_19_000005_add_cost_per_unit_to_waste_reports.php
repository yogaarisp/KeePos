<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_reports', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 15, 2)->default(0)->after('unit')
                  ->comment('Harga per unit saat waste dicatat');
        });
    }

    public function down(): void
    {
        Schema::table('waste_reports', function (Blueprint $table) {
            $table->dropColumn('cost_per_unit');
        });
    }
};
