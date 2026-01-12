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
    Schema::create('bridging_surat_kontrol_bpjs', function (Blueprint $table) {
        $table->id();
        $table->string('no_surat_kontrol')->unique(); 
        $table->string('no_sep'); 
        $table->string('no_kartu'); 
        $table->string('nama_pasien');
        $table->string('no_rm')->nullable(); 
        $table->date('tgl_rencana_kontrol');
        $table->string('kode_poli');
        $table->string('nama_poli');
        $table->string('kode_dokter');
        $table->string('nama_dokter');
        $table->string('jam_praktek');
        $table->string('status')->default('Belum Booking'); 
        $table->string('kode_booking')->nullable(); 
        $table->integer('nomor_antrean')->nullable();
        $table->string('estimasi_dilayani')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridging_surat_kontrol_bpjs');
    }
};
