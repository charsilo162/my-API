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
        Schema::create('center_tutor', function (Blueprint $table) {
            // Foreign key to the Center
            $table->foreignId('center_id')
                  ->constrained('centers')
                  ->onDelete('cascade');
            
            // Foreign key to the Tutor
            // $table->foreignId('tutor_id')
            //       ->constrained('tutors')
            //       ->onDelete('cascade');
            // Foreign key to the Tutor
            $table->foreignId('tutor_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Primary composite key to ensure a tutor is linked to a center only once
            $table->primary(['center_id', 'tutor_id']); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('center_tutor');
    }
};