<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        // Add is_active to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('email');
        });

        // Add is_active to centers
        Schema::table('centers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
        
        // Note: Courses already have 'publish', so we can use that!
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_active'));
        Schema::table('centers', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};