<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BridgingSuratKontrolBpjs extends Model
{
    protected $table = 'bridging_surat_kontrol_bpjs';

    protected $fillable = [
        'no_surat_kontrol', 'no_sep', 'no_kartu', 'nama_pasien', 'no_rm',
        'tgl_rencana_kontrol', 'kode_poli', 'nama_poli', 'kode_dokter', 'nama_dokter',
        'jam_praktek', 'status','sisa_kuota', 'kode_booking', 'nomor_antrean', 'estimasi_dilayani'
    ];

    protected $dates = ['tgl_rencana_kontrol'];
}