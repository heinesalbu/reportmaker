<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('template_sections', function (Blueprint $table) {
        $table->boolean('show_title')->default(true);
    });
}

public function down()
{
    Schema::table('template_sections', function (Blueprint $table) {
        $table->dropColumn('show_title');
    });
}
};
