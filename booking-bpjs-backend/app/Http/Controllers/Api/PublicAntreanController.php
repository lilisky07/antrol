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
            $perPage = $request->get('per_page', 50);
            $page = $request->get('page', 1);

            $query = DB::table('bridging_surat_kontrol_bpjs as bsk')
                ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
                ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
                ->join('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                ->leftJoin('poliklinik', 'rp.kd_poli', '=', 'poliklinik.kd_poli')
                ->leftJoin('dokter', 'rp.kd_dokter', '=', 'dokter.kd_dokter')
                ->select(
                    'pasien.no_rkm_medis as no_rm',
                    'pasien.nm_pasien as nama',
                    'bsk.no_surat as no_surat',               // NO SURAT KONTROL
                    'bsk.tgl_surat as tgl_surat',
                    'bsk.tgl_rencana as tgl_rencana',         // TANGGAL RENCANA KONTROL
                    'poliklinik.nm_poli as poli',
                    'dokter.nm_dokter as dokter',
                    DB::raw("'Belum Booking' as status"),
                    'bs.no_sep as kode_booking',
                    DB::raw('NULL as nomor_antrean'),
                    DB::raw('0 as sisa_kuota')
                )
                ->where('rp.stts', '!=', 'Batal') // Hindari registrasi batal
                ->whereNotNull('bsk.no_surat')    // Pastikan ada surat kontrol
                ->orderBy('bsk.tgl_rencana', 'asc') // Urut berdasarkan tanggal rencana terdekat
                ->orderBy('rp.tgl_registrasi', 'desc');

            // Filter search (no_rm, nama, no_surat)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('pasien.nm_pasien', 'like', "%{$search}%")
                      ->orWhere('pasien.no_rkm_medis', 'like', "%{$search}%")
                      ->orWhere('bsk.no_surat', 'like', "%{$search}%")
                      ->orWhere('bs.no_sep', 'like', "%{$search}%");
                });
            }

            // Filter tanggal surat kontrol
            if ($request->filled('tgl_surat')) {
                $query->whereDate('bsk.tgl_surat', $request->tgl_surat);
            }

            // Filter tanggal rencana kontrol
            if ($request->filled('tgl_rencana')) {
                $query->whereDate('bsk.tgl_rencana', $request->tgl_rencana);
            }

            // Filter poli BPJS
            if ($request->filled('poli')) {
                $query->where('poliklinik.nm_poli', 'like', '%' . $request->poli . '%');
            }

            // Filter dokter BPJS
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
                'message' => 'Error mengambil data rencana kontrol: ' . $e->getMessage()
            ], 500);
        }
    }
}