<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->boolean('selected')->default(false);
            $table->text('override_text')->nullable();
            $table->timestamps();

            $table->unique(['project_id','block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_blocks');
    }
};
