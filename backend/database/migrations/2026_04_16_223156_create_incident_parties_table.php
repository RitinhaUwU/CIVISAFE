<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incident_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents');
            $table->foreignId('entity_id')->constrained('entities');
            $table->integer('vehicle_count');
            $table->integer('human_count');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_parties');
    }
};
