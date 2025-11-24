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
        Schema::create('center_course', function (Blueprint $table) {
            // Foreign key to the Center (The physical venue)
            $table->foreignId('center_id')
                  ->constrained('centers')
                  ->onDelete('cascade');
            
            // Foreign key to the Course
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->onDelete('cascade');

            // Metadata for the specific offering at this center
            $table->decimal('price', 10, 2)->nullable(); // Specific price at this center
            $table->date('start_date')->nullable(); // Start date of the class session
            $table->date('end_date')->nullable();   // End date of the class session

            // Primary composite key to ensure a course is listed only once per center
            $table->primary(['center_id', 'course_id']); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('center_course');
    }
};