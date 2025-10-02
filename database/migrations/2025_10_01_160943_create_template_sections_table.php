<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->boolean('included')->default(true);
            $table->string('title_override')->nullable();
            $table->unsignedInteger('order_override')->default(0);
            $table->timestamps();

            $table->unique(['template_id','section_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('template_sections'); }
};
