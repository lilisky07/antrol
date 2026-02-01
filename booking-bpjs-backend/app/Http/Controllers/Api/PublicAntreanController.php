<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AmbilAntreanRequest;
use App\Http\Requests\NomorAntreanRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PublicAntreanController extends Controller
{
   
    // public function list(Request $request): JsonResponse
    // {
    //     try {
    //         $query = DB::table('bridging_surat_kontrol_bpjs as bsk')
    //             ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
    //             ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
    //             ->join('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
    //             ->join('maping_poli_bpjs as maping_poli', 'maping_poli.kd_poli_bpjs', '=', 'bsk.kd_poli_bpjs')
    //             ->join('maping_dokter_dpjpvclaim as maping_dokter', 'maping_dokter.kd_dokter_bpjs', '=', 'bsk.kd_dokter_bpjs')
    //             ->join('dokter', 'dokter.kd_dokter', '=', 'maping_dokter.kd_dokter')
    //             ->join('poliklinik', 'poliklinik.kd_poli', '=', 'maping_poli.kd_poli_rs')
    //             // LEFT JOIN yang berat dipindah ke subquery
    //             ->leftJoin(
    //                 DB::raw('(SELECT nomorreferensi, no_rawat, status as status_rmb
    //                          FROM referensi_mobilejkn_bpjs
    //                          WHERE status IN ("Gagal","Batal","Belum","Checkin")) as rmb'),
    //                 'rmb.nomorreferensi', '=', 'bsk.no_surat'
    //             )
    //             ->where('rp.stts', '!=', 'Batal')
    //             ->whereNotNull('bsk.no_surat');

    //         // Filter tanggal rencana - PENTING untuk performa (kasih default range)
    //         if ($request->filled('tgl_rencana')) {


    //         // Kalau user pilih tanggal spesifik
    //         $query->whereDate('bsk.tgl_rencana', $request->get('tgl_rencana'));
    //         } else {
    //         $query->whereBetween('bsk.tgl_rencana', [
    //         now()->subMonths(2)->format('Y-m-d'),
    //         now()->format('Y-m-d')
    //         ]);
    //         }

    //         if ($request->filled('search')) {
    //             $search = $request->get('search');
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('pasien.nm_pasien', 'like', "%{$search}%")
    //                   ->orWhere('pasien.no_rkm_medis', 'like', "%{$search}%")
    //                   ->orWhere('bsk.no_surat', 'like', "%{$search}%")
    //                   ->orWhere('bs.no_sep', 'like', "%{$search}%");
    //             });
    //         }

    //         if ($request->filled('tgl_surat')) {
    //             $query->whereDate('bsk.tgl_surat', $request->get('tgl_surat'));
    //         }

    //         if ($request->filled('poli')) {
    //             $polis = explode(',', $request->get('poli'));
    //             $query->whereIn('bsk.nm_poli_bpjs', $polis);
    //         }

    //         if ($request->filled('dokter')) {
    //             $query->where('dokter.nm_dokter', 'like', '%' . $request->get('dokter') . '%');
    //         }

    //         $query->select(
    //             'pasien.no_rkm_medis as no_rm',
    //             'pasien.nm_pasien as nama',
    //             'bsk.no_surat',
    //             'bsk.tgl_surat',
    //             'bsk.tgl_rencana',
    //             'poliklinik.nm_poli as poli',
    //             'dokter.nm_dokter as dokter',
    //             'poliklinik.kd_poli',
    //             'dokter.kd_dokter',
    //             DB::raw("COALESCE(rmb.status_rmb, 'Belum') as status"),
    //             DB::raw("IF(rmb.status_rmb = 'Checkin', true, false) as is_booked"),
    //             'bs.no_sep as kode_booking',
    //             DB::raw('NULL as nomor_antrean')
    //         );

    //         $query->orderBy('bsk.tgl_rencana', 'desc')
    //               ->orderBy('bsk.tgl_surat', 'desc');

            
    //         $perPage = min((int) $request->get('per_page', 20), 50); // Max 50 per page
            
            
    //       $poliList = $query->paginate($perPage);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $poliList
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Error list antrean', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error mengambil data rencana kontrol: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
public function list(Request $request): JsonResponse
{
    try {
        $query = DB::table('bridging_surat_kontrol_bpjs as bsk')
            ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
            ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
            ->join('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('maping_poli_bpjs as maping_poli', 'maping_poli.kd_poli_bpjs', '=', 'bsk.kd_poli_bpjs')
            ->join('maping_dokter_dpjpvclaim as maping_dokter', 'maping_dokter.kd_dokter_bpjs', '=', 'bsk.kd_dokter_bpjs')
            ->join('dokter', 'dokter.kd_dokter', '=', 'maping_dokter.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli', '=', 'maping_poli.kd_poli_rs')
            // LEFT JOIN yang berat dipindah ke subquery
            ->leftJoin(
                DB::raw('(SELECT nomorreferensi, no_rawat, status as status_rmb
                         FROM referensi_mobilejkn_bpjs
                         WHERE status IN ("Gagal","Batal","Belum","Checkin")) as rmb'),
                'rmb.nomorreferensi', '=', 'bsk.no_surat'
            )
            ->where('rp.stts', '!=', 'Batal')
            ->whereNotNull('bsk.no_surat');

        // Filter tanggal rencana - hanya data hari ini dan ke depan
        if ($request->filled('tgl_rencana')) {
            // Kalau user pilih tanggal spesifik
            $query->whereDate('bsk.tgl_rencana', $request->get('tgl_rencana'));
        } else {
            // Default: hanya tampilkan data hari ini dan ke depan (data lama tidak ditampilkan)
            $query->whereDate('bsk.tgl_rencana', '>=', now()->format('Y-m-d'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('pasien.nm_pasien', 'like', "%{$search}%")
                  ->orWhere('pasien.no_rkm_medis', 'like', "%{$search}%")
                  ->orWhere('bsk.no_surat', 'like', "%{$search}%")
                  ->orWhere('bs.no_sep', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tgl_surat')) {
            $query->whereDate('bsk.tgl_surat', $request->get('tgl_surat'));
        }

        if ($request->filled('poli')) {
            $polis = explode(',', $request->get('poli'));
            $query->whereIn('bsk.nm_poli_bpjs', $polis);
        }

        if ($request->filled('dokter')) {
            $query->where('dokter.nm_dokter', 'like', '%' . $request->get('dokter') . '%');
        }

        $query->select(
            'pasien.no_rkm_medis as no_rm',
            'pasien.nm_pasien as nama',
            'bsk.no_surat',
            'bsk.tgl_surat',
            'bsk.tgl_rencana',
            'poliklinik.nm_poli as poli',
            'dokter.nm_dokter as dokter',
            'poliklinik.kd_poli',
            'dokter.kd_dokter',
            DB::raw("COALESCE(rmb.status_rmb, 'Belum') as status"),
            DB::raw("IF(rmb.status_rmb = 'Checkin', true, false) as is_booked"),
            'bs.no_sep as kode_booking',
            DB::raw('NULL as nomor_antrean')
        );

        // Urutkan ASC agar yang paling dekat duluan (hari ini, besok, lusa, dst)
        $query->orderBy('bsk.tgl_rencana', 'desc')
              ->orderBy('bsk.tgl_surat', 'desc');

        $perPage = min((int) $request->get('per_page', 20), 50); // Max 50 per page
        
        $poliList = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $poliList
        ]);
    } catch (\Exception $e) {
        Log::error('Error list antrean', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error mengambil data rencana kontrol: ' . $e->getMessage(),
        ], 500);
    }
}

    public function getPoliList()
     { try 
     { $poliList = DB::table('bridging_surat_kontrol_bpjs as bsk') ->
     join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep') ->
     join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat') ->
     where('rp.stts', '!=', 'Batal') ->whereNotNull('bsk.no_surat') ->
     whereNotNull('bsk.nm_poli_bpjs') ->select('bsk.nm_poli_bpjs as nm_poli')
      ->distinct() ->orderBy('bsk.nm_poli_bpjs', 'asc') ->pluck('nm_poli');
       return response()->json([ 'success' => true, 'data' => $poliList ]); } catch 
       (\Exception $e) { return response()->json([ 'success' => false, 'message' => $e->getMessage() ], 500); } }




    public function ambilAntrean(AmbilAntreanRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'no_rm'       => 'required|string',
                'no_surat'    => 'required|string',
                'kd_poli'     => 'required|string',
                'kd_dokter'   => 'required|string',
                'tgl_antrean' => 'required|date'
            ]);

            $tglAntrean = Carbon::parse($request->tgl_antrean);

            $pasien = DB::table('pasien')
                ->where('no_rkm_medis', $request->no_rm)
                ->first();

            if (!$pasien) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Pasien tidak ditemukan'], 404);
            }

            // Ambil data surat kontrol
            $surat = DB::table('bridging_surat_kontrol_bpjs as bsk')
                ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
                ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
                ->join('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                ->where('bsk.no_surat', $request->no_surat)
                ->where('pasien.no_rkm_medis', $request->no_rm)
                ->select(
                    'bsk.*',
                    'pasien.tgl_lahir as tgl_lahir_pasien',
                    'pasien.nm_pasien'
                )
                ->first();

            if (!$surat) {
                DB::rollBack();
                return response()->json([
                    'success' => false, 
                    'message' => 'Surat kontrol tidak ditemukan'
                ], 404);
            }

            // Cek apakah sudah terdaftar
            $existingBooking = DB::table('referensi_mobilejkn_bpjs')
                ->where('norm', $request->no_rm)
                ->where('nomorreferensi', $request->no_surat)
                ->whereIn('status', ['Checkin', 'Belum', 'Batal'])
                ->first(['nobooking', 'no_rawat', 'nomorantrean', 'tanggalperiksa', 'estimasidilayani']);

            if ($existingBooking) {
                $existingReg = DB::table('reg_periksa')
                    ->where('no_rawat', $existingBooking->no_rawat)
                    ->first(['no_reg']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pasien sudah terdaftar di antrean sebelumnya',
                    'data' => [
                        'nobooking'       => $existingBooking->nobooking,
                        'no_rawat'        => $existingBooking->no_rawat,
                        'no_reg'          => $existingReg ? str_pad($existingReg->no_reg, 3, '0', STR_PAD_LEFT) : null,
                        'nomorantrean'    => $existingBooking->nomorantrean,
                        'tanggal'         => $existingBooking->tanggalperiksa,
                        'estimasi'        => $existingBooking->estimasidilayani ? date('H:i', $existingBooking->estimasidilayani) : null,
                        'sisa_kuota'      => null,
                    ]
                ]);
            }

            // Cek jadwal dokter
            $jadwal = DB::table('jadwal')
                ->where('kd_dokter', $request->kd_dokter)
                ->where('kd_poli', $request->kd_poli)
                ->where('hari_kerja', $this->getNamaHari($tglAntrean))
                ->first(['kuota', 'jam_mulai']);

            if (!$jadwal) {
                throw ValidationException::withMessages([
                    "tgl_antrean" => "Jadwal dokter tidak ditemukan pada tanggal tersebut",
                ]);
            }

            // Hitung jumlah antrean
            $jumlahAntrean = DB::table("reg_periksa")
                ->where('kd_dokter', $request->kd_dokter)
                ->where('tgl_registrasi', $tglAntrean->format('Y-m-d'))
                ->whereNot('stts', 'Batal')
                ->count();

            if ($jumlahAntrean >= $jadwal->kuota) {
                throw ValidationException::withMessages([
                    "tgl_antrean" => "Antrean sudah penuh",
                ]);
            }

            // Generate no_rawat
            $tglRawat = $tglAntrean->format("Y/m/d");
            $lastRawat = DB::table('reg_periksa')
                ->where('no_rawat', 'like', $tglRawat . '%')
                ->orderBy('no_rawat', 'desc')
                ->first([DB::raw("CONVERT(RIGHT(reg_periksa.no_rawat,6),signed) as no_rawat")]);

            $newNumber = ($lastRawat->no_rawat ?? 0) + 1;
            $noRawat = $tglRawat . '/' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

            // Generate nobooking
            $prefixBooking = $tglAntrean->format('Ymd');
            $lastBooking = DB::table('referensi_mobilejkn_bpjs')
                ->where('nobooking', 'like', $prefixBooking . '%')
                ->orderBy('nobooking', 'desc')
                ->first([DB::raw('CONVERT(RIGHT(nobooking,6),signed) as nobooking')]);

            $newNumBooking = ($lastBooking->nobooking ?? 0) + 1;
            $noBooking = $prefixBooking . str_pad($newNumBooking, 6, '0', STR_PAD_LEFT);

            $nomorAntrean = $jumlahAntrean + 1;

            // no_reg (per poli + dokter + tanggal)
            $noReg = DB::table('reg_periksa')
                ->whereDate('tgl_registrasi', $tglAntrean->format('Y-m-d'))
                ->where('kd_poli', $request->kd_poli)
                ->where('kd_dokter', $request->kd_dokter)
                ->count() + 1;

            $jamMulai = substr($jadwal->jam_mulai, 0, 5);

            $estimasiMenit = ($nomorAntrean - 1) * 10;
            $estimasiWaktu = strtotime($tglAntrean->format('Y-m-d') . ' ' . $jamMulai) + ($estimasiMenit * 60);

            // Hitung umur
            $lahir = new \DateTime($surat->tgl_lahir_pasien);
            $diff = $lahir->diff($tglAntrean);

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

            // Biaya registrasi
            $biayaReg = DB::table('poliklinik')
                ->where('kd_poli', $request->kd_poli)
                ->value('registrasilama') ?? 0;

            $dataRegPeriksa = [
                'no_reg'         => str_pad($noReg, 3, '0', STR_PAD_LEFT),
                'no_rawat'       => $noRawat,
                'tgl_registrasi' => $tglAntrean->format('Y-m-d'),
                'jam_reg'        => now()->format('H:i:s'),
                'kd_dokter'      => $request->kd_dokter,
                'no_rkm_medis'   => $request->no_rm,
                'kd_poli'        => $request->kd_poli,
                'p_jawab'        => $surat->nm_pasien,
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

            Log::info('Insert reg_periksa', $dataRegPeriksa);

            if (!DB::table('reg_periksa')->insert($dataRegPeriksa)) {
                throw new \Exception('Gagal insert reg_periksa');
            }

            // Insert ke referensi_mobilejkn_bpjs
            $dataReferensi = [
                'nobooking'         => $noBooking,
                'no_rawat'          => $noRawat,
                'norm'              => $request->no_rm,
                'tanggalperiksa'    => $tglAntrean->format('Y-m-d'),
                'kodepoli'          => $request->kd_poli,
                'kodedokter'        => $request->kd_dokter,
                'jampraktek'        => $jamMulai,
                'jeniskunjungan'    => 3, // Kontrol
                'nomorreferensi'    => $request->no_surat,
                'nomorantrean'      => str_pad($nomorAntrean, 3, '0', STR_PAD_LEFT),
                'angkaantrean'      => $nomorAntrean,
                'estimasidilayani'  => $estimasiWaktu,
                'sisakuotajkn'      => $jadwal->kuota - $nomorAntrean,
                'kuotajkn'          => $jadwal->kuota,
                'status'            => 'Booking',
                'validasi'          => now(),
            ];

            if (!DB::table('referensi_mobilejkn_bpjs')->insert($dataReferensi)) {
                throw new \Exception('Gagal insert referensi_mobilejkn_bpjs');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil antrean',
                'data' => [
                    'nobooking'    => $noBooking,
                    'no_rawat'     => $noRawat,
                    'no_reg'       => str_pad($noReg, 3, '0', STR_PAD_LEFT),
                    'nomorantrean' => str_pad($nomorAntrean, 3, '0', STR_PAD_LEFT),
                    'tanggal'      => $tglAntrean->format('Y-m-d'),
                    'estimasi'     => date('H:i', $estimasiWaktu),
                    'sisa_kuota'   => $jadwal->kuota - $nomorAntrean,
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error ambilAntrean', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil antrean: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getNamaHari(Carbon $tanggal): string
    {
        $hari = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
        return $hari[$tanggal->dayOfWeek];
    }

    public function nomorAntrean(NomorAntreanRequest $request): JsonResponse
    {
        $tgl_antrean = Carbon::parse($request->tgl_antrean);
        $weekday = $this->getNamaHari($tgl_antrean);

        $data = DB::table("jadwal")
            ->where('kd_dokter', $request->kd_dokter)
            ->where('hari_kerja', $weekday)
            ->first(['kuota']);

        if (!$data) {
            throw ValidationException::withMessages([
                "kd_dokter" => "Tidak ada jadwal dokter pada tanggal tersebut",
            ]);
        }

        $count_regis = DB::table('reg_periksa')
            ->where('tgl_registrasi', $tgl_antrean->format('Y-m-d'))
            ->where('kd_dokter', $request->kd_dokter)
            ->where('stts', '<>', 'Batal')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'sisa_kuota' => $data->kuota - $count_regis,
            ],
        ]);
    }

    public function cekAntrean(Request $request): JsonResponse
    {
        try {
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

            if ($request->filled('no_rm')) {
                $query->where('rmj.norm', $request->no_rm);
            }

            if ($request->filled('tanggal')) {
                $query->whereDate('rmj.tanggalperiksa', $request->tanggal);
            }

            $data = $query->orderBy('rmj.validasi', 'desc')->get();

            return response()->json([
                'success' => true,
                'data'    => $data,
                'count'   => $data->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cekRegPeriksa(Request $request): JsonResponse
    {
        try {
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

            if ($request->filled('no_rawat')) {
                $query->where('rp.no_rawat', $request->no_rawat);
            }

            if ($request->filled('no_rm')) {
                $query->where('rp.no_rkm_medis', $request->no_rm);
            }

            if ($request->filled('tanggal')) {
                $query->whereDate('rp.tgl_registrasi', $request->tanggal);
            }

            $data = $query->orderByDesc('rp.tgl_registrasi')
                          ->orderByDesc('rp.jam_reg')
                          ->get();

            return response()->json([
                'success' => true,
                'data'    => $data,
                'count'   => $data->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetailAntrean(Request $request)
    {
        try {
            $noRawat = $request->get('no_rawat');
            $noRm = $request->get('no_rm');

            if (!$noRawat && !$noRm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter no_rawat atau no_rm harus diisi'
                ], 400);
            }

            $query = DB::table('referensi_mobilejkn_bpjs as rmj')
                ->leftJoin('reg_periksa as rp', 'rmj.no_rawat', '=', 'rp.no_rawat')
                ->leftJoin('pasien as p', 'rmj.norm', '=', 'p.no_rkm_medis')
                ->leftJoin('poliklinik as pol', 'rmj.kodepoli', '=', 'pol.kd_poli')
                ->leftJoin('dokter as d', 'rmj.kodedokter', '=', 'd.kd_dokter')
                ->select(
                    'rmj.nobooking',
                    'rmj.no_rawat',
                    'rmj.norm as no_rm',
                    'p.nm_pasien as nama_pasien',
                    'p.no_peserta as no_kartu_bpjs',
                    'p.no_ktp as nik',
                    'p.no_tlp as no_hp',
                    'rmj.tanggalperiksa',
                    'rmj.kodepoli',
                    'pol.nm_poli as nama_poli',
                    'rmj.kodedokter',
                    'd.nm_dokter as nama_dokter',
                    'rmj.jampraktek',
                    'rmj.jeniskunjungan',
                    'rmj.nomorreferensi as no_surat_kontrol',
                    'rmj.nomorantrean',
                    'rmj.angkaantrean',
                    'rmj.estimasidilayani',
                    'rmj.sisakuotajkn',
                    'rmj.kuotajkn',
                    'rmj.status',
                    'rmj.validasi',
                    'rmj.statuskirim',
                    'rp.no_reg',
                    'rp.stts as status_periksa',
                    'rp.status_bayar',
                    'rp.biaya_reg'
                );

            if ($noRawat) {
                $query->where('rmj.no_rawat', $noRawat);
            } else {
                $query->where('rmj.norm', $noRm);
            }

            $data = $query->orderBy('rmj.validasi', 'desc')->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data antrean tidak ditemukan'
                ], 404);
            }

            // Format estimasi dilayani
            $data = $data->map(function($item) {
                $item->estimasi_waktu = $item->estimasidilayani 
                    ? date('H:i', $item->estimasidilayani) 
                    : null;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $noRawat ? $data->first() : $data,
                'count' => $data->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getDetailAntrean', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function getDokterList()
    // {
    //     try {
    //         $dokterList = DB::table('bridging_surat_kontrol_bpjs as bsk')
    //             ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
    //             ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
    //             ->where('rp.stts', '!=', 'Batal')
    //             ->whereNotNull('bsk.no_surat')
    //             ->whereNotNull('bsk.nm_dokter_bpjs')
    //             ->select('bsk.nm_dokter_bpjs as nm_dokter')
    //             ->distinct()
    //             ->orderBy('bsk.nm_dokter_bpjs', 'asc')
    //             ->pluck('nm_dokter');

    //         return response()->json([
    //             'success' => true,
    //             'data' => $dokterList
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}