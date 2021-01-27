<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('gejala');
        Schema::dropIfExists('riwayat_penyakit_penyerta');
        Schema::dropIfExists('riwayat_kunjungan');
        Schema::dropIfExists('gejala_pasien');
        Schema::dropIfExists('riwayat_kontak');
        Schema::dropIfExists('pemeriksaan_penunjang');
        Schema::dropIfExists('riwayat_lawatan');
        Schema::dropIfExists('penyakit_penyerta');
        Schema::dropIfExists('ekstraksi');
        Schema::dropIfExists('pengambilan_sampel_registrasi');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
