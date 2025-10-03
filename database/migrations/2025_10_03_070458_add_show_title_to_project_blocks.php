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
        // Vi legger til show_title her selv om det egentlig handler om seksjoner,
        // fordi det ikke finnes en project_sections-tabell. 
        // Vi kan lagre seksjon-visibility per prosjekt i en egen tabell.
    });
    
    // Eller lag en helt ny tabell for project-section overrides:
    Schema::create('project_sections', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained()->onDelete('cascade');
        $table->foreignId('section_id')->constrained()->onDelete('cascade');
        $table->boolean('show_title')->nullable(); // null = arv fra template
        $table->timestamps();
        
        $table->unique(['project_id', 'section_id']);
    });
}

public function down()
{
    Schema::dropIfExists('project_sections');
}
};
