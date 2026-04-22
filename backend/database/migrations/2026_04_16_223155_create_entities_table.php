<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('entity_type_id')->constrained('entity_types');
            $table->string('phone_contact')->nullable();
            $table->string('email_contact')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('poc_name')->nullable();
            $table->string('poc_phone')->nullable();
            $table->string('poc_email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
