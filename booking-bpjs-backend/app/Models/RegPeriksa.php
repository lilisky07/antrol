<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegPeriksa extends Model
{
    protected $table = 'reg_periksa';

    protected $fillable = [
        'no_reg', 'no_rawat', 'no_rm', 'nm_pasien', 'tgl_registrasi',
        'status_lanjut', 'kd_poli', 'kd_dokter', 'stts'
    ];
}