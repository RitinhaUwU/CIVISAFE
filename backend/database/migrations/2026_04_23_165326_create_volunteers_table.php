<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact');
            $table->string('email');
            $table->string('num_elements');
            $table->longText('mission')->nullable();
            $table->string('team_identification')->nullable();
            $table->string('classification');
            $table->string('location')->nullable();
            $table->boolean('has_accommodation');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->foreignId('incident_id')->nullable()->constrained('incidents');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteers');
    }
};
