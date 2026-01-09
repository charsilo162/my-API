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
       Schema::create('courses', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('restrict');

            $table->foreignId('uploader_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->foreignId('assigned_tutor_id')
                ->nullable()
                ->constrained('tutors')
                ->onDelete('set null');

            // Core Course Details
            $table->string('title');
            $table->string('slug')->unique()->nullable(); // ✅ Add this line
            $table->text('description')->nullable();
            $table->string('image_thumbnail_url')->nullable();

            // Course Type
            $table->enum('type', ['online', 'physical', 'hybrid'])->default('online');
            //$table->integer('publish')->default(0);

            $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};