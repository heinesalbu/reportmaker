<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('project_custom_blocks', function (Blueprint $table) {
        $table->unsignedBigInteger('after_block_id')->nullable()->after('section_id');
        $table->json('tips')->nullable()->after('text');
    });
}

public function down()
{
    Schema::table('project_custom_blocks', function (Blueprint $table) {
        $table->dropColumn(['after_block_id', 'tips']);
    });
}

};
