<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();       // f.eks. "webanalyse_v1"
            $table->string('name');                // "Webanalyse V1"
            $table->text('description')->nullable();
            $table->json('meta')->nullable();      // evt. css-profiler o.l.
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('templates'); }
};
