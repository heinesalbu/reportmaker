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
        // database/migrations/xxxx_xx_xx_xxxxxx_create_project_custom_blocks_table.php
        Schema::create('project_custom_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('section_id');
            $table->string('label')->nullable();   // overskrift/tittel
            $table->string('icon')->nullable();    // valgfritt ikon
            $table->string('severity')->nullable(); // info/warn/crit om ønskelig
            $table->text('text')->nullable();      // innhold
            $table->integer('order')->default(0);  // for å styre rekkefølge innen seksjonen
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_custom_blocks');
    }
};
