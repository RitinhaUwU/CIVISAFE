<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incident_pcos', function (Blueprint $table) {
            $table->id();
            $table->string('function_pco');
            $table->string('resp_pco');
            $table->string('category_pco');
            $table->string('contact1_pco');
            $table->string('contact2_pco')->nullable();
            $table->string('localization_pco')->nullable();
            $table->string('rob_pco')->nullable();
            $table->string('srp_pco')->nullable();
            $table->dateTime('activation_pco_datetime')->nullable();
            $table->dateTime('start_pco_datetime');
            $table->dateTime('end_pco_datetime')->nullable();
            $table->foreignId('incident_id')->nullable()->constrained('incidents');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_pcos');
    }
};
