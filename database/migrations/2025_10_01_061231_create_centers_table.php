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
       Schema::create('centers', function (Blueprint $table) {
            $table->id();
            
            // Core Center Information
            $table->string('name', 100);
            $table->string('address');
            $table->text('description')->nullable();
            $table->string('city', 50)->nullable();
            $table->unsignedSmallInteger('years_of_experience')->default(0);
            $table->string('center_thumbnail_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
