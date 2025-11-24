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
        Schema::table('course_user', function (Blueprint $table) {
            // Add column to store the unique Paystack transaction reference
            $table->string('payment_reference')->after('user_id')->nullable()->unique();
            
            // Add column for the amount the user paid (to record the historical price)
            $table->decimal('paid_amount', 8, 2)->after('payment_reference')->nullable();
            
            // Add column to record the exact time the payment was confirmed
            $table->timestamp('paid_at')->after('paid_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'paid_amount', 'paid_at']);
        });
    }
};
