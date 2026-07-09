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
        Schema::table('incidents', function (Blueprint $table) {
            $table->longText('operational_grid')->nullable();
            $table->string('identifier')->nullable()->change();
            $table->unique('identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropUnique(['identifier']);
            $table->dropColumn('operational_grid');
            $table->string('identifier')->nullable(false)->change();
        });
    }
};
