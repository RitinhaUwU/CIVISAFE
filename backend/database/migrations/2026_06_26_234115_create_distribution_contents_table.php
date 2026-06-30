<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('distribution_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_distribution_id')->constrained('donation_distributions');
            $table->foreignId('donation_goods_type_id')->constrained('donation_goods_types');
            $table->float('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_contents');
    }
};
