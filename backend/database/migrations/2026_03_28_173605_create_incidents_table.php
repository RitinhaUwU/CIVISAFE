<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->foreignId('category_code')->constrained('categories', 'code');
            $table->foreignId('incident_state_id')->constrained('incident_states');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('incident_priority_id')->constrained('incident_priorities');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->string('coordinates')->nullable();
            $table->string('common_place')->nullable();
            $table->string('address')->nullable();
            $table->string('parish')->nullable();
            $table->string('municipality')->nullable();
            $table->string('district')->nullable();
            $table->string('command_post');
            $table->boolean('is_major');
            $table->string('alert_source_relationship')->nullable();
            $table->string('alert_source_name')->nullable();
            $table->string('alert_source_contact')->nullable();
            $table->longText('obs')->nullable();
            $table->foreignId('incident_id')->nullable()->constrained('incidents');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
