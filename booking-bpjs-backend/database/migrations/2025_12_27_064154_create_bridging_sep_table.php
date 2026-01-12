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
    Schema::create('bridging_sep', function (Blueprint $table) {
        $table->id();
        $table->string('no_sep')->unique();
        $table->string('no_kartu');
        $table->string('nama_pasien');
        $table->string('no_rm')->nullable();
        $table->date('tgl_sep');
        $table->string('jns_pelayanan'); 
        $table->string('poli');
        $table->string('diagnosa');
        $table->string('kelas_rawat');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridging_sep');
    }
};
