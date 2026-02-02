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
        // Update Courses Table
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('publish');
        });

        // Update Videos Table
        Schema::table('videos', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('publish');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};