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
    Schema::table('template_blocks', function (Blueprint $table) {
        $table->boolean('show_icon')->default(true);
        $table->boolean('show_label')->default(true);
        $table->boolean('show_text')->default(true);
        $table->boolean('show_tips')->default(true);
        $table->boolean('show_severity')->default(false);
    });
}

public function down()
{
    Schema::table('template_blocks', function (Blueprint $table) {
        $table->dropColumn(['show_icon', 'show_label', 'show_text', 'show_tips', 'show_severity']);
    });
}
};
