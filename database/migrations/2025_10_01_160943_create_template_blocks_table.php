<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('template_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();

            $table->boolean('included')->default(true);

            // Overstyringer (alle valgfrie)
            $table->string('icon_override', 32)->nullable();
            $table->string('label_override')->nullable();
            $table->string('severity_override')->nullable();   // info|warn|crit
            $table->text('default_text_override')->nullable();
            $table->json('tips_override')->nullable();         // ["tips1","tips2"]
            $table->json('references_override')->nullable();   // ["https://..."]
            $table->json('tags_override')->nullable();

            $table->unsignedInteger('order_override')->default(0);
            $table->boolean('visible_by_default_override')->nullable();

            $table->timestamps();

            $table->unique(['template_id','block_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('template_blocks'); }
};
