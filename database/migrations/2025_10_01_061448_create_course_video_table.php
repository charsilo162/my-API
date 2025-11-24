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
       
Schema::create('course_video', function (Blueprint $table) {
    $table->foreignId('course_id')->constrained()->onDelete('cascade');
    $table->foreignId('video_id')->constrained()->onDelete('cascade');
    
    $table->unsignedSmallInteger('order_index')->default(0); 

    $table->primary(['course_id', 'video_id']); 
    
    $table->unique(['course_id', 'order_index']); 
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_video');
    }
};