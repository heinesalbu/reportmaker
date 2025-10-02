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
        Schema::table('project_blocks', function (Blueprint $table) {
            $table->string('override_label')->nullable()->after('override_text');
            $table->string('override_icon')->nullable()->after('override_label');
            $table->json('override_tips')->nullable()->after('override_icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_blocks', function (Blueprint $table) {
            // Sjekk om kolonnene finnes før de slettes, for sikkerhets skyld
            if (Schema::hasColumn('project_blocks', 'override_label')) {
                $table->dropColumn('override_label');
            }
            if (Schema::hasColumn('project_blocks', 'override_icon')) {
                $table->dropColumn('override_icon');
            }
            if (Schema::hasColumn('project_blocks', 'override_tips')) {
                $table->dropColumn('override_tips');
            }
        });
    }
};