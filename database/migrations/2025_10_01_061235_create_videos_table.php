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
 // database/migrations/..._create_videos_table.php
Schema::create('videos', function (Blueprint $table) {
    $table->id();

    // The mandatory foreign key to the Tutor (Owner/Uploader)
    $table->foreignId('tutor_id')
          ->constrained('tutors')
          ->onDelete('cascade'); 
    
    // Video Content Details
    $table->string('title');
    $table->string('video_url');
    $table->string('thumbnail_url')->nullable();
    $table->unsignedInteger('duration')->nullable();
    
    // REMOVE $table->foreignId('course_id')
    // REMOVE $table->unsignedSmallInteger('order_index')
    // REMOVE $table->unique(['course_id', 'order_index'])
    
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};