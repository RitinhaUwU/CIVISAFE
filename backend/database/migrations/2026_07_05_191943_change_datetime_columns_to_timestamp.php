<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('timeline_comments', function (Blueprint $table) {
            $table->timestampTz('datetime')->change();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->timestampTz('start_datetime')->change();
            $table->timestampTz('end_datetime')->nullable()->change();
        });

        Schema::table('incident_pcos', function (Blueprint $table) {
            $table->timestampTz('activation_pco_datetime')->nullable()->change();
            $table->timestampTz('start_pco_datetime')->change();
            $table->timestampTz('end_pco_datetime')->nullable()->change();
        });

        Schema::table('volunteers', function (Blueprint $table) {
            $table->timestampTz('start_datetime')->change();
            $table->timestampTz('end_datetime')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('timeline_comments', function (Blueprint $table) {
            $table->dateTime('datetime')->change();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dateTime('start_datetime')->change();
            $table->dateTime('end_datetime')->nullable()->change();
        });

        Schema::table('incident_pcos', function (Blueprint $table) {
            $table->dateTime('activation_pco_datetime')->nullable()->change();
            $table->dateTime('start_pco_datetime')->change();
            $table->dateTime('end_pco_datetime')->nullable()->change();
        });

        Schema::table('volunteers', function (Blueprint $table) {
            $table->dateTime('start_datetime')->change();
            $table->dateTime('end_datetime')->nullable()->change();
        });
    }
};
