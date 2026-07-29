<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->string('rejection_reason')->nullable()->after('payment_proof_path');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'reviewed_at', 'reviewed_by']);
        });
    }
};
