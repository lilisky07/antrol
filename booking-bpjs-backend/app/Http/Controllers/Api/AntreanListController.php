<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BridgingSuratKontrolBpjs;
use Illuminate\Http\Request;
use Carbon\Carbon;
class AntreanListController extends Controller
{
    

public function list(Request $request)
{
    try {
        $query = DB::table('bridging_surat_kontrol_bpjs as sk')
            ->leftJoin('reg_periksa as rp', 'sk.no_rawat', '=', 'rp.no_rawat')
            ->leftJoin('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->leftJoin('poliklinik', 'sk.kd_poli', '=', 'poliklinik.kd_poli')
            ->leftJoin('dokter', 'sk.kd_dokter', '=', 'dokter.kd_dokter')
            ->select(
                'pasien.no_rkm_medis as no_rm',
                'pasien.nm_pasien as nama',
                'sk.tgl_kontrol as tgl_surat',
                'poliklinik.nm_poli as poli',
                'dokter.nm_dokter as dokter',
                'sk.status',
                'sk.kode_booking',
                'sk.no_antrian as nomor_antrean',
                DB::raw('COALESCE(sk.sisa_kuota, 0) as sisa_kuota')
            )
            ->whereDate('sk.tgl_kontrol', '>=', now())
            ->orderBy('sk.tgl_kontrol', 'desc');

        $antrean = $query->get();

        return response()->json([
            'success' => true,
            'data' => $antrean
        ]);
    } catch (\Exception $e) {
        \Log::error('Public Antrean Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error server: ' . $e->getMessage()
        ], 500);
    }
}


}