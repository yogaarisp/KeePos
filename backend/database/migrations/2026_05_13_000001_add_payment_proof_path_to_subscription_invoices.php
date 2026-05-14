<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_invoices', 'payment_proof_path')) {
                $table->string('payment_proof_path', 512)->nullable()->after('payment_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_invoices', 'payment_proof_path')) {
                $table->dropColumn('payment_proof_path');
            }
        });
    }
};
