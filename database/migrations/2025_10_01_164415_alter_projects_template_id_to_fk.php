<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Hvis kolonnen finnes og ikke allerede er big int:
        if (Schema::hasColumn('projects','template_id')) {
            // Hvis den var string tidligere, prøv å kaste til int (ellers null)
            DB::statement("UPDATE projects SET template_id = NULL WHERE template_id REGEXP '^[0-9]+$' = 0");
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->change();
            });
        } else {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->after('title');
            });
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            // valgfritt: endre tilbake til string om nødvendig
            // $table->string('template_id')->nullable()->change();
        });
    }
};
