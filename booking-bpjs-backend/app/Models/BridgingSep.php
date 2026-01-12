<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BridgingSep extends Model
{
    protected $table = 'bridging_sep';

    protected $fillable = [
        'no_sep', 'no_kartu', 'nama_pasien', 'no_rm', 'tgl_sep',
        'jns_pelayanan', 'poli', 'diagnosa', 'kelas_rawat'
    ];
}