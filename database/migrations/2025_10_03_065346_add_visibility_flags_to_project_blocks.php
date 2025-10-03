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
    Schema::table('project_blocks', function (Blueprint $table) {
        $table->boolean('show_icon')->nullable();
        $table->boolean('show_label')->nullable();
        $table->boolean('show_text')->nullable();
        $table->boolean('show_tips')->nullable();
        $table->boolean('show_severity')->nullable();
    });
}

public function down()
{
    Schema::table('project_blocks', function (Blueprint $table) {
        $table->dropColumn(['show_icon', 'show_label', 'show_text', 'show_tips', 'show_severity']);
    });
}
};
