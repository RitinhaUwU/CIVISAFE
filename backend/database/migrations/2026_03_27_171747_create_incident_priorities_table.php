<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incident_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description');
            $table->string('hex_color');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_priorities');
    }
};
