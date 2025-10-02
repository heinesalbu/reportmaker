<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('template_id')->nullable();  // f.eks. "magitek_webanalyse_v1"
            $table->unsignedBigInteger('owner_id')->nullable(); // senere: users
            $table->string('status')->default('draft'); // draft|ready|exported
            $table->json('tags')->nullable();           // ["seo","design"]
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
