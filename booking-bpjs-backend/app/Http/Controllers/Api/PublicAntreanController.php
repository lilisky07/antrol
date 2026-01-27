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
        $perPage = $request->get('per_page', 30);

        // Query utama
        $query = DB::table('bridging_surat_kontrol_bpjs as bsk')
            ->whereNotNull('bsk.no_surat')
            ->where('bsk.tgl_rencana', '>=', now()->subYears(3));

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('bsk.no_surat', 'like', "%{$search}%")
                  ->orWhere('bsk.no_sep', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tgl_rencana')) {
            $query->whereDate('bsk.tgl_rencana', $request->get('tgl_rencana'));
        }

        if ($request->filled('poli')) {
            $query->whereIn('bsk.nm_poli_bpjs', explode(',', $request->get('poli')));
        }

        $query->select(
            'bsk.no_surat',
            'bsk.tgl_surat',
            'bsk.tgl_rencana',
            'bsk.nm_poli_bpjs as poli',
            'bsk.nm_dokter_bpjs as dokter',
            'bsk.no_sep'
        )
        ->orderBy('bsk.tgl_rencana', 'desc')
        ->orderBy('bsk.tgl_surat', 'desc');

        $antrean = $query->simplePaginate($perPage);
        
        $noSepList = collect($antrean->items())->pluck('no_sep')->toArray();
        
        // Ambil data pasien
        $pasienData = DB::table('bridging_sep as bs')
            ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
            ->join('pasien as p', 'rp.no_rkm_medis', '=', 'p.no_rkm_medis')
            ->whereIn('bs.no_sep', $noSepList)
            ->where('rp.stts', '!=', 'Batal')
            ->select(
                'bs.no_sep',
                'p.no_rkm_medis as no_rm',
                'p.nm_pasien as nama',
                'rp.kd_poli',
                'rp.kd_dokter'
            )
            ->get()
            ->keyBy('no_sep');

        // ✅ CEK BOOKING - status 'Belum' atau 'Checkin' = sudah booking
        $noSuratList = collect($antrean->items())->pluck('no_surat')->toArray();
        
        $bookedData = DB::table('referensi_mobilejkn_bpjs')
            ->whereIn('nomorreferensi', $noSuratList)
            ->whereIn('status', ['Belum', 'Checkin']) // ✅ Cek status Belum atau Checkin
            ->select('nomorreferensi', 'status', 'nobooking', 'nomorantrean', 'no_rawat')
            ->get()
            ->keyBy('nomorreferensi');

        Log::info('Booked Data Count', ['count' => $bookedData->count()]);
        Log::info('No Surat List', ['list' => $noSuratList]);
        Log::info('Booked Data', ['data' => $bookedData->toArray()]);

        // Gabungkan data
        $data = collect($antrean->items())->map(function($item) use ($pasienData, $bookedData) {
            $pasien = $pasienData->get($item->no_sep);
            $booking = $bookedData->get($item->no_surat);
            
            return [
                'no_surat'       => $item->no_surat,
                'no_rm'          => $pasien->no_rm ?? '-',
                'nama'           => $pasien->nama ?? '-',
                'tgl_surat'      => $item->tgl_surat,
                'tgl_rencana'    => $item->tgl_rencana,
                'poli'           => $item->poli,
                'dokter'         => $item->dokter,
                'kd_poli'        => $pasien->kd_poli ?? null,
                'kd_dokter'      => $pasien->kd_dokter ?? null,
                'isBooked'       => $booking ? true : false, // ✅ True kalau ada booking
                'status_booking' => $booking->status ?? null, // Belum/Checkin
                'nobooking'      => $booking->nobooking ?? null,
                'nomorantrean'   => $booking->nomorantrean ?? null,
                'no_rawat'       => $booking->no_rawat ?? null,
            ];
        })->toArray();

        return response()->json([
            'success'      => true,
            'data'         => $data,
            'current_page' => $antrean->currentPage(),
            'next_page'    => $antrean->hasMorePages() ? $antrean->currentPage() + 1 : null,
            'per_page'     => $antrean->perPage(),
        ]);
    } catch (\Exception $e) {
        Log::error('Error list antrean: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error mengambil data: ' . $e->getMessage()
        ], 500);
    }
}

 public function getPoliList()
    {
        try {
            $poliList = DB::table('bridging_surat_kontrol_bpjs as bsk')
                ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
                ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
                ->where('rp.stts', '!=', 'Batal')
                ->whereNotNull('bsk.no_surat')
                ->whereNotNull('bsk.nm_poli_bpjs')
                ->select('bsk.nm_poli_bpjs as nm_poli')
                ->distinct()
                ->orderBy('bsk.nm_poli_bpjs', 'asc')
                ->pluck('nm_poli');

            return response()->json([
                'success' => true,
                'data' => $poliList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function ambilAntrean(Request $request)
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

            $tglAntrean = date('Y-m-d', strtotime($request->tgl_antrean));

            $pasien = DB::table('pasien')
                ->where('no_rkm_medis', $request->no_rm)
                ->first();

            if (!$pasien) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Pasien tidak ditemukan'], 404);
            }

            // todo cek udh daftr ap blm 
            $existingBooking = DB::table('referensi_mobilejkn_bpjs')
                ->where('norm', $request->no_rm)
                ->where('nomorreferensi', $request->no_surat)
                ->where('status', 'Booking')   // sesuaikan kalau statusnya beda (misal 'Sudah')
                ->first(['nobooking', 'no_rawat', 'nomorantrean', 'tanggalperiksa', 'estimasidilayani']);

            if ($existingBooking) {
                $existingReg = DB::table('reg_periksa')
                    ->where('no_rawat', $existingBooking->no_rawat)
                    ->first(['no_reg']);

                DB::commit(); // tetap commit meski tidak insert baru

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
                        'sisa_kuota'      => null, // bisa hitung ulang kalau perlu
                    ]
                ]);
            }
            // ────────────────────────────────────────────────

            // Jika belum ada → lanjut proses insert normal

            // Cek kuota
            $jumlahAntrean = DB::table('referensi_mobilejkn_bpjs')
                ->where('tanggalperiksa', $tglAntrean)
                ->where('kodepoli', $request->kd_poli)
                ->count();

            $kuotaTotal = 30;
            if ($jumlahAntrean >= $kuotaTotal) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Kuota antrean sudah penuh'], 400);
            }

            // Generate no_rawat
            $tglRawat = date('Y/m/d', strtotime($tglAntrean));
            $lastRawat = DB::table('reg_periksa')
                ->where('no_rawat', 'like', $tglRawat . '%')
                ->orderBy('no_rawat', 'desc')
                ->first(['no_rawat']);

            $newNumber = $lastRawat ? (int) substr($lastRawat->no_rawat, -6) + 1 : 1;
            $noRawat = $tglRawat . '/' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

            // Generate nobooking
            $prefixBooking = date('Ymd', strtotime($tglAntrean));
            $lastBooking = DB::table('referensi_mobilejkn_bpjs')
                ->where('nobooking', 'like', $prefixBooking . '%')
                ->orderBy('nobooking', 'desc')
                ->first(['nobooking']);

            $newNumBooking = $lastBooking ? (int) substr($lastBooking->nobooking, -6) + 1 : 1;
            $noBooking = $prefixBooking . str_pad($newNumBooking, 6, '0', STR_PAD_LEFT);

            $nomorAntrean = $jumlahAntrean + 1;

            // no_reg
            $noReg = DB::table('reg_periksa')
                ->whereDate('tgl_registrasi', $tglAntrean)
                ->where('kd_poli', $request->kd_poli)
                ->where('kd_dokter', $request->kd_dokter)
                ->count() + 1;

            // Jadwal
            $jadwal = DB::table('jadwal')
                ->where('kd_dokter', $request->kd_dokter)
                ->where('kd_poli', $request->kd_poli)
                ->where('hari_kerja', $this->getNamaHari($tglAntrean))
                ->first(['jam_mulai']);

            $jamMulai = $jadwal ? substr($jadwal->jam_mulai, 0, 5) : '08:00';

            // Estimasi
            $estimasiMenit = ($nomorAntrean - 1) * 10;
            $estimasiWaktu = strtotime($tglAntrean . ' ' . $jamMulai) + ($estimasiMenit * 60);

            // Umur pasien
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

            // Biaya registrasi
            $biayaReg = DB::table('poliklinik')
                ->where('kd_poli', $request->kd_poli)
                ->value('registrasilama') ?? 0;

            $dataRegPeriksa = [
                'no_reg'         => str_pad($noReg, 3, '0', STR_PAD_LEFT),
                'no_rawat'       => $noRawat,
                'tgl_registrasi' => $tglAntrean,
                'jam_reg'        => now()->format('H:i:s'),
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

            Log::info('Insert reg_periksa', $dataRegPeriksa);

            if (!DB::table('reg_periksa')->insert($dataRegPeriksa)) {
                throw new \Exception('Gagal insert reg_periksa');
            }

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
                'jampraktek'       => $jamMulai,
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

            Log::info('Insert referensi_mobilejkn_bpjs', ['nobooking' => $noBooking]);

            if (!DB::table('referensi_mobilejkn_bpjs')->insert($dataReferensi)) {
                throw new \Exception('Gagal insert referensi_mobilejkn_bpjs');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Antrean berhasil diambil',
                'data'    => [
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
            Log::error('Error ambilAntrean', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getNamaHari($tanggal)
    {
        $hari = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
        return $hari[date('w', strtotime($tanggal))];
    }

    // Method debug tetap dipertahankan
    public function cekAntrean(Request $request)
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
                'count'   => $data->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cekRegPeriksa(Request $request)
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
                'count'   => $data->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get detail antrean berdasarkan no_rawat atau no_rm
     */
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


    /**
     * Get list semua dokter untuk dropdown filter
     */
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