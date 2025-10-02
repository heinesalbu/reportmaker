<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('key')->index();        // "mangler_sitemap"
            $table->string('label');               // "Mangler sitemap"
            $table->string('icon')->nullable();    // filnavn/emoji
            $table->string('severity')->default('info'); // info|warn|crit
            $table->text('default_text')->nullable();
            $table->json('tips')->nullable();        // ["Gjør X", "Gjør Y"]
            $table->json('references')->nullable();  // ["https://..."]
            $table->json('tags')->nullable();        // ["seo","teknisk"]
            $table->boolean('visible_by_default')->default(true);
            $table->timestamps();

            $table->unique(['section_id','key']); // unik innen seksjon
        });
    }
    public function down(): void { Schema::dropIfExists('blocks'); }
};
