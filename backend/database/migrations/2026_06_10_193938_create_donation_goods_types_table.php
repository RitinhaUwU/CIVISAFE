<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donation_goods_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_type_countable');
            $table->string('unit')->nullable();
            $table->float('danger_level')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_goods_types');
    }
};
