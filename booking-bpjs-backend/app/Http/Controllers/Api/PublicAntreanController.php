<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                ->where('rp.stts', '!=', 'Batal') 
                ->whereNotNull('bsk.no_surat');

            // Filter search (no_rm, nama, no_surat)
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('pasien.nm_pasien', 'like', "%{$search}%")
                      ->orWhere('pasien.no_rkm_medis', 'like', "%{$search}%")
                      ->orWhere('bsk.no_surat', 'like', "%{$search}%")
                      ->orWhere('bs.no_sep', 'like', "%{$search}%");
                });
            }

            // Filter tanggal surat kontrol
            if ($request->filled('tgl_surat')) {
                $query->whereDate('bsk.tgl_surat', $request->get('tgl_surat'));
            }

            // Filter tanggal rencana kontrol
            if ($request->filled('tgl_rencana')) {
                $query->whereDate('bsk.tgl_rencana', $request->get('tgl_rencana'));
            }

            // Filter poli BPJS
            if ($request->filled('poli')) {
                $query->where('poliklinik.nm_poli', 'like', '%' . $request->get('poli') . '%');
            }

            // Filter dokter BPJS
            if ($request->filled('dokter')) {
                $query->where('dokter.nm_dokter', 'like', '%' . $request->get('dokter') . '%');
            }

            $query->select(
                'pasien.no_rkm_medis as no_rm',
                'pasien.nm_pasien as nama',
                'bsk.no_surat as no_surat',               
                'bsk.tgl_surat as tgl_surat',
                'bsk.tgl_rencana as tgl_rencana',         
                'poliklinik.nm_poli as poli',
                'dokter.nm_dokter as dokter',
                'rp.kd_poli',
                'rp.kd_dokter',
                DB::raw("'Belum Booking' as status"),
                'bs.no_sep as kode_booking',
                DB::raw('NULL as nomor_antrean'),
                // Hitung sisa kuota berdasarkan data real
                DB::raw("(SELECT 
                    COALESCE(30 - COUNT(*), 30) 
                    FROM referensi_mobilejkn_bpjs 
                    WHERE tanggalperiksa = bsk.tgl_rencana 
                    AND kodepoli = rp.kd_poli
                ) as sisa_kuota")
            )
            ->orderBy('bsk.tgl_rencana', 'asc') 
            ->orderBy('rp.tgl_registrasi', 'desc');

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

    public function ambilAntrean(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'no_rm'       => 'required',
                'no_surat'    => 'required',
                'kd_poli'     => 'required',
                'kd_dokter'   => 'required',
                'tgl_antrean' => 'required|date'
            ]);

            // normalisasi tanggal
            $tglAntrean = date('Y-m-d', strtotime($request->tgl_antrean));

            // ambil pasien
            $pasien = DB::table('pasien')
                ->where('no_rkm_medis', $request->no_rm)
                ->first();

            if (!$pasien) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pasien tidak ditemukan'
                ], 404);
            }

            // Cek kuota tersedia
            $jumlahAntrean = DB::table('referensi_mobilejkn_bpjs')
                ->where('tanggalperiksa', $tglAntrean)
                ->where('kodepoli', $request->kd_poli)
                ->count();

            $kuotaTotal = 30;
            if ($jumlahAntrean >= $kuotaTotal) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Kuota antrean sudah penuh untuk tanggal dan poli tersebut'
                ], 400);
            }

            // Generate no_rawat dengan TANGGAL PERIKSA
            $tglRawat = date('Y/m/d', strtotime($tglAntrean));
            $lastRawat = DB::table('reg_periksa')
                ->where('no_rawat', 'like', $tglRawat . '%')
                ->orderBy('no_rawat', 'desc')
                ->first();

            if ($lastRawat) {
                $lastNumber = (int) substr($lastRawat->no_rawat, -6);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $noRawat = $tglRawat . '/' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

            // Generate booking number
            $prefixBooking = date('Ymd', strtotime($tglAntrean));
            $lastBooking = DB::table('referensi_mobilejkn_bpjs')
                ->where('nobooking', 'like', $prefixBooking . '%')
                ->orderBy('nobooking', 'desc')
                ->first();

            if ($lastBooking) {
                $lastNumBooking = (int) substr($lastBooking->nobooking, -6);
                $newNumBooking = $lastNumBooking + 1;
            } else {
                $newNumBooking = 1;
            }

            $noBooking = $prefixBooking . str_pad($newNumBooking, 6, '0', STR_PAD_LEFT);

            // Hitung nomor antrean
            $nomorAntrean = $jumlahAntrean + 1;

            // Hitung no_reg
            $noReg = DB::table('reg_periksa')
                ->whereDate('tgl_registrasi', $tglAntrean)
                ->where('kd_poli', $request->kd_poli)
                ->where('kd_dokter', $request->kd_dokter)
                ->count() + 1;

            // Ambil data jadwal
            $jadwal = DB::table('jadwal')
                ->where('kd_dokter', $request->kd_dokter)
                ->where('kd_poli', $request->kd_poli)
                ->where('hari_kerja', $this->getNamaHari($tglAntrean))
                ->first();

            $jamPraktek = $jadwal ? $jadwal->jam_mulai : '08:00:00';
            $jamMulai = $jadwal ? substr($jadwal->jam_mulai, 0, 5) : '08:00';
            
            // Hitung estimasi dilayani
            $estimasiMenit = ($nomorAntrean - 1) * 10;
            $estimasiWaktu = strtotime($tglAntrean . ' ' . $jamMulai) + ($estimasiMenit * 60);

            // Hitung umur pasien
            $umur = 0;
            $sttsumur = 'Th';
            if ($pasien->tgl_lahir) {
                $lahir = new \DateTime($pasien->tgl_lahir);
                $today = new \DateTime('today');
                $diff = $lahir->diff($today);
                
                if ($diff->y > 0) {
                    $umur = $diff->y;
                    $sttsumur = 'Th';
                } elseif ($diff->m > 0) {
                    $umur = $diff->m;
                    $sttsumur = 'Bl';
                } else {
                    $umur = $diff->d;
                    $sttsumur = 'Hr';
                }
            }

            // Ambil biaya registrasi dari poliklinik
            $biayaReg = DB::table('poliklinik')
                ->where('kd_poli', $request->kd_poli)
                ->value('registrasi') ?? 0;

            // Data untuk insert reg_periksa
            $dataRegPeriksa = [
                'no_reg'         => str_pad($noReg, 3, '0', STR_PAD_LEFT),
                'no_rawat'       => $noRawat,
                'tgl_registrasi' => $tglAntrean,
                'jam_reg'        => date('H:i:s'),
                'kd_dokter'      => $request->kd_dokter,
                'no_rkm_medis'   => $request->no_rm,
                'kd_poli'        => $request->kd_poli,
                'p_jawab'        => $pasien->nm_pasien,
                'almt_pj'        => $pasien->alamat ?? '-',
                'hubunganpj'     => 'KELUARGA',
                'biaya_reg'      => $biayaReg,
                'stts'           => 'Belum',
                'stts_daftar'    => 'Lama',
                'status_lanjut'  => 'Ralan',
                'kd_pj'          => 'BPJ',
                'umurdaftar'     => $umur,
                'sttsumur'       => $sttsumur,
                'status_bayar'   => 'Belum Bayar',
                'status_poli'    => 'Lama'
            ];

            Log::info('Attempting to insert reg_periksa', $dataRegPeriksa);

            // Insert ke reg_periksa
            $insertRegPeriksa = DB::table('reg_periksa')->insert($dataRegPeriksa);

            if (!$insertRegPeriksa) {
                DB::rollBack();
                Log::error('Failed to insert reg_periksa', $dataRegPeriksa);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal insert data registrasi periksa'
                ], 500);
            }

            Log::info('Successfully inserted reg_periksa', ['no_rawat' => $noRawat]);

            // Data untuk insert referensi_mobilejkn_bpjs
            $dataReferensi = [
                'nobooking'        => $noBooking,
                'no_rawat'         => $noRawat,
                'nomorkartu'       => $pasien->no_peserta ?? '',
                'nik'              => $pasien->no_ktp ?? '',
                'nohp'             => $pasien->no_tlp ?? '',
                'kodepoli'         => $request->kd_poli,
                'pasienbaru'       => '0',
                'norm'             => $request->no_rm,
                'tanggalperiksa'   => $tglAntrean,
                'kodedokter'       => $request->kd_dokter,
                'jampraktek'       => $jamPraktek,
                'jeniskunjungan'   => '2 (Rujukan Internal)',
                'nomorreferensi'   => $request->no_surat,
                'nomorantrean'     => $nomorAntrean,
                'angkaantrean'     => $nomorAntrean,
                'estimasidilayani' => $estimasiWaktu,
                'sisakuotajkn'     => $kuotaTotal - $nomorAntrean,
                'sisakuotanonjkn'  => 0,
                'kuotajkn'         => $kuotaTotal,
                'kuotanonjkn'      => 0,
                'status'           => 'Belum',
                'validasi'         => now(),
                'statuskirim'      => 'Belum'
            ];

            Log::info('Attempting to insert referensi_mobilejkn_bpjs', $dataReferensi);

            $insertReferensi = DB::table('referensi_mobilejkn_bpjs')->insert($dataReferensi);

            if (!$insertReferensi) {
                DB::rollBack();
                Log::error('Failed to insert referensi_mobilejkn_bpjs', $dataReferensi);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal insert data referensi mobile JKN'
                ], 500);
            }

            Log::info('Successfully inserted referensi_mobilejkn_bpjs', ['nobooking' => $noBooking]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Antrean berhasil diambil',
                'data' => [
                    'nobooking'       => $noBooking,
                    'no_rawat'        => $noRawat,
                    'no_reg'          => str_pad($noReg, 3, '0', STR_PAD_LEFT),
                    'nomorantrean'    => $nomorAntrean,
                    'tanggal'         => $tglAntrean,
                    'estimasi'        => date('H:i', $estimasiWaktu),
                    'sisa_kuota'      => $kuotaTotal - $nomorAntrean
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ambilAntrean', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Hitung umur berdasarkan tanggal lahir
     */
    private function hitungUmur($tglLahir)
    {
        $lahir = new \DateTime($tglLahir);
        $today = new \DateTime('today');
        return $lahir->diff($today)->y;
    }

    /**
     * Get nama hari dalam bahasa Indonesia
     */
    private function getNamaHari($tanggal)
    {
        $hari = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
        return $hari[date('w', strtotime($tanggal))];
    }

    /**
     * Cek data antrean yang sudah diambil (untuk debugging)
     */
    public function cekAntrean(Request $request)
    {
        try {
            $noRm = $request->get('no_rm');
            $tanggal = $request->get('tanggal');

            $query = DB::table('referensi_mobilejkn_bpjs as rmj')
                ->leftJoin('reg_periksa as rp', 'rmj.no_rawat', '=', 'rp.no_rawat')
                ->leftJoin('poliklinik as p', 'rmj.kodepoli', '=', 'p.kd_poli')
                ->leftJoin('dokter as d', 'rmj.kodedokter', '=', 'd.kd_dokter')
                ->select(
                    'rmj.*',
                    'rp.stts as status_registrasi',
                    'rp.no_reg',
                    'p.nm_poli',
                    'd.nm_dokter'
                );

            if ($noRm) {
                $query->where('rmj.norm', $noRm);
            }

            if ($tanggal) {
                $query->whereDate('rmj.tanggalperiksa', $tanggal);
            }

            $data = $query->orderBy('rmj.validasi', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => $data->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cek apakah data masuk ke reg_periksa (debugging)
     */
    public function cekRegPeriksa(Request $request)
    {
        try {
            $noRawat = $request->get('no_rawat');
            $noRm = $request->get('no_rm');
            $tanggal = $request->get('tanggal');

            $query = DB::table('reg_periksa as rp')
                ->leftJoin('pasien as p', 'rp.no_rkm_medis', '=', 'p.no_rkm_medis')
                ->leftJoin('poliklinik as pol', 'rp.kd_poli', '=', 'pol.kd_poli')
                ->leftJoin('dokter as d', 'rp.kd_dokter', '=', 'd.kd_dokter')
                ->select(
                    'rp.*',
                    'p.nm_pasien',
                    'pol.nm_poli',
                    'd.nm_dokter'
                );

            if ($noRawat) {
                $query->where('rp.no_rawat', $noRawat);
            }

            if ($noRm) {
                $query->where('rp.no_rkm_medis', $noRm);
            }

            if ($tanggal) {
                $query->whereDate('rp.tgl_registrasi', $tanggal);
            }

            $data = $query->orderBy('rp.tgl_registrasi', 'desc')
                          ->orderBy('rp.jam_reg', 'desc')
                          ->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => $data->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}