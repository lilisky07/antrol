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

            $query = DB::table('bridging_surat_kontrol_bpjs as bsk')
                ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
                ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
                ->join('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                ->leftJoin('poliklinik', 'rp.kd_poli', '=', 'poliklinik.kd_poli')
                ->leftJoin('dokter', 'rp.kd_dokter', '=', 'dokter.kd_dokter')
                ->leftJoin(DB::raw("(
                    SELECT tanggalperiksa, kodepoli, COUNT(*) as booked
                    FROM referensi_mobilejkn_bpjs
                    GROUP BY tanggalperiksa, kodepoli
                ) as kuota"), function ($join) {
                    $join->on('kuota.tanggalperiksa', '=', 'bsk.tgl_rencana')
                         ->on('kuota.kodepoli', '=', 'rp.kd_poli');
                })
                ->where('rp.stts', '!=', 'Batal')
                ->whereNotNull('bsk.no_surat');

            // Filter search
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
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

            // Filter poli
            if ($request->filled('poli')) {
                $query->where('poliklinik.nm_poli', 'like', '%' . $request->get('poli') . '%');
            }

            // Filter dokter
            if ($request->filled('dokter')) {
                $query->where('dokter.nm_dokter', 'like', '%' . $request->get('dokter') . '%');
            }

            $query->select(
                'pasien.no_rkm_medis as no_rm',
                'pasien.nm_pasien as nama',
                'bsk.no_surat as no_surat',
                'bsk.tgl_surat as tgl_surat',
                'bsk.tgl_rencana as tgl_rencana',
                'bsk.nm_poli_bpjs as poli',
                'bsk.nm_dokter_bpjs as dokter',
                'rp.kd_poli',
                'rp.kd_dokter',
                DB::raw("'Belum Booking' as status"),
                'bs.no_sep as kode_booking',
                DB::raw('NULL as nomor_antrean'),
                DB::raw('COALESCE(30 - kuota.booked, 30) as sisa_kuota')
            )
            ->orderBy('bsk.tgl_rencana', 'asc')
            ->orderBy('rp.tgl_registrasi', 'desc');

            // Gunakan simplePaginate agar tidak hitung total record (lebih cepat)
            $antrean = $query->simplePaginate($perPage);

            return response()->json([
                'success'      => true,
                'data'         => $antrean->items(),
                'current_page' => $antrean->currentPage(),
                'next_page'    => $antrean->hasMorePages() ? $antrean->currentPage() + 1 : null,
                'per_page'     => $antrean->perPage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error list antrean: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
}