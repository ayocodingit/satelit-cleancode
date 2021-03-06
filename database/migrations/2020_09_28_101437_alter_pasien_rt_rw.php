<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPasienRtRw extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->string('no_rw', 10)->nullable(true)->change();
            $table->string('no_rt', 10)->nullable(true)->change();
            $table->bigInteger('kode_kelurahan')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->char('no_rw', 3)->nullable(true)->change();
            $table->char('no_rt', 3)->nullable(true)->change();
            $table->integer('kode_kelurahan')->nullable(true)->change();
        });
    }
}
