<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donation_audits', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_type');
            $table->foreignId('donation_goods_type_id')->constrained('donation_goods_types');
            $table->float('quantity');
            $table->string('reason');
            $table->longText('obs')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_audits');
    }
};
