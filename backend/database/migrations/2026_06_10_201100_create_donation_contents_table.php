<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donation_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_log_id')->constrained('donation_logs');
            $table->foreignId('donation_goods_types_id')->constrained('donation_goods_types');
            $table->float('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_contents');
    }
};
