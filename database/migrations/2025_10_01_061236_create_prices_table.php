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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();

            // Link to the Course
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->onDelete('cascade'); // If a course is deleted, its price history is deleted

            // Price Details
            $table->decimal('amount', 10, 2); // Price amount (e.g., 99.99)
            $table->string('currency', 3)->default('USD'); // Currency code (e.g., USD, EUR)
            
            // Flag to indicate the currently active price
            $table->boolean('is_current')->default(true); 

            $table->timestamps();

            // Optional: Index on course_id and is_current for quick lookup of the current price
            $table->index(['course_id', 'is_current']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};