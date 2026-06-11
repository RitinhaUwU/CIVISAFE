<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donation_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_goods_type_id')->constrained('donation_goods_types');
            $table->float('stock');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_stocks');
    }
};
