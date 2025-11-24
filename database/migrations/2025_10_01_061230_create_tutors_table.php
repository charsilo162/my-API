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
    Schema::create('tutors', function (Blueprint $table) {
            $table->id();

            // The Tutor MUST be linked to a User account
            $table->foreignId('user_id')
                  ->unique() // One user can only be one tutor
                  ->constrained('users')
                  ->onDelete('cascade');

            // Professional Details
            $table->text('bio')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutors');
    }
};
