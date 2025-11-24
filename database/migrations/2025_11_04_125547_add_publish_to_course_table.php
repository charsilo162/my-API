<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('publish')->default(false)->after('category_id');
        });
    }

    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('publish');
        });
    }
};