<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_add_order_to_blocks_table.php
return new class extends Migration {
    public function up(): void {
        Schema::table('blocks', function (Blueprint $t) {
            $t->unsignedInteger('order')->default(0)->after('label');
        });
    }
    public function down(): void {
        Schema::table('blocks', function (Blueprint $t) {
            $t->dropColumn('order');
        });
    }
};

