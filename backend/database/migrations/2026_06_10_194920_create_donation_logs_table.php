<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('date');
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('donor_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_logs');
    }
};
