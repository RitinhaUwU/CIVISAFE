<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incident_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('hex_color');
            $table->boolean('terminates_incident')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_states');
    }
};
