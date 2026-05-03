<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment');
            $table->dateTime('date');
            $table->string('event');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_events');
    }
};
