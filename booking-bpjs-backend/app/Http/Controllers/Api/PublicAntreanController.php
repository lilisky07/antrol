<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicAntreanController extends Controller
{
  public function list(Request $request)
{
    try {
        $perPage = $request->get('per_page', 50); // 50 data per halaman
        $page = $request->get('page', 1);

        $query = DB::table('bridging_sep as bs')
            ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
            ->join('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('poliklinik', 'rp.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('dokter', 'rp.kd_dokter', '=', 'dokter.kd_dokter')
            ->select(
                'pasien.no_rkm_medis as no_rm',
                'pasien.nm_pasien as nama',
                // 'bs.tgl_suratkontrol as tgl_kontrol',
                'rp.tgl_registrasi as tgl_surat',
                'poliklinik.nm_poli as poli',
                'dokter.nm_dokter as dokter',
                DB::raw("'Belum Booking' as status"),
                'bs.no_sep as kode_booking',
                DB::raw('NULL as nomor_antrean'),
                DB::raw('0 as sisa_kuota')
            )
            ->where('rp.stts', '!=', 'Batal')
            ->orderBy('rp.tgl_registrasi', 'desc')
            ->orderBy('rp.jam_reg', 'asc');

        // Filter search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pasien.nm_pasien', 'like', "%{$search}%")
                  ->orWhere('pasien.no_rkm_medis', 'like', "%{$search}%")
                  ->orWhere('bs.no_sep', 'like', "%{$search}%")
                  ->orWhere('rp.no_rawat', 'like', "%{$search}%");
            });
        }

        // Filter tanggal
        if ($request->filled('tgl_surat')) {
            $query->whereDate('rp.tgl_registrasi', $request->tgl_surat);
        }

        // Filter poli
        if ($request->filled('poli')) {
            $query->where('poliklinik.nm_poli', 'like', '%' . $request->poli . '%');
        }

        // Filter dokter
        if ($request->filled('dokter')) {
            $query->where('dokter.nm_dokter', 'like', '%' . $request->dokter . '%');
        }

        $antrean = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $antrean->items(),
            'current_page' => $antrean->currentPage(),
            'last_page' => $antrean->lastPage(),
            'total' => $antrean->total(),
            'per_page' => $antrean->perPage(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
}