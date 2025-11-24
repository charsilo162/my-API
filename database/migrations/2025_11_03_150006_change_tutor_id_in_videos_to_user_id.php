<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            // Change column to reference users.id instead of tutors.id
            $table->dropForeign(['tutor_id']);
            $table->dropColumn('tutor_id');

            $table->foreignId('uploader_user_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['uploader_user_id']);
            $table->dropColumn('uploader_user_id');

            $table->foreignId('tutor_id')
                  ->nullable()
                  ->constrained('tutors')
                  ->onDelete('set null');
        });
    }
};