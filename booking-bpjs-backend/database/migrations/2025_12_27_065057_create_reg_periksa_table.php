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
    Schema::create('reg_periksa', function (Blueprint $table) {
        $table->id();
        $table->string('no_reg')->unique();
        $table->string('no_rawat')->unique();
        $table->string('no_rm');
        $table->string('nm_pasien');
        $table->date('tgl_registrasi');
        $table->string('status_lanjut'); // Rawat Jalan / Rawat Inap
        $table->string('kd_poli')->nullable();
        $table->string('kd_dokter')->nullable();
        $table->string('stts'); // Belum / Sudah / Batal dll
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reg_periksa');
    }
};
