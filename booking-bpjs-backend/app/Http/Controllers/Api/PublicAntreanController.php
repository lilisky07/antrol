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
    public function list(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 30);

            $query = DB::table('bridging_surat_kontrol_bpjs as bsk')
                ->join('bridging_sep as bs', 'bsk.no_sep', '=', 'bs.no_sep')
                ->join('reg_periksa as rp', 'bs.no_rawat', '=', 'rp.no_rawat')
                ->join('pasien', 'rp.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                ->join(
                    'maping_poli_bpjs as maping_poli',
                    'maping_poli.kd_poli_bpjs',
                    '=',
                    'bsk.kd_poli_bpjs'
                )
                ->join(
                    'maping_dokter_dpjpvclaim as maping_dokter',
                    'maping_dokter.kd_dokter_bpjs',
                    '=',
                    'bsk.kd_dokter_bpjs'
                )
                ->join('dokter', 'dokter.kd_dokter', '=', 'maping_dokter.kd_dokter')
                ->join('poliklinik', 'poliklinik.kd_poli', '=', 'maping_poli.kd_poli_rs')
                ->leftJoin(
                    'referensi_mobilejkn_bpjs as rmb',
                    function ($joinClause) {
                        $joinClause->on('rmb.nomorreferensi', '=', 'bsk.no_surat')
                        ->whereIn('rmb.status', ['Booking','Belum','Checkin']);
                    }
                )
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
                'poliklinik.nm_poli as poli',
                'dokter.nm_dokter as dokter',
                'poliklinik.kd_poli',
                'dokter.kd_dokter',
                DB::raw("!isnull(rmb.no_rawat) as status"),
                'bs.no_sep as kode_booking',
                DB::raw('NULL as nomor_antrean'),
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
                'message' => 'Error mengambil data rencana kontrol: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function ambilAntrean(AmbilAntreanRequest $request): JsonResponse
    {
        $tglAntrean = Carbon::parse($request->tgl_antrean);
        if (
            !($surat = DB::table('bridging_surat_kontrol_bpjs as bsk')
                        ->join('bridging_sep as bs', 'bs.no_sep', '=', 'bsk.no_sep')
                        ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'bs.no_rawat')
                        ->join('pasien as p', 'p.no_rkm_medis', '=', 'rp.no_rkm_medis')
                        ->join('maping_poli_bpjs as maping_poli', 'maping_poli.kd_poli_bpjs', '=', 'bsk.kd_poli_bpjs')
                        ->join(
                            'maping_dokter_dpjpvclaim as maping_dokter',
                            'maping_dokter.kd_dokter_bpjs',
                            '=',
                            'bsk.kd_dokter_bpjs'
                        )
                        ->join("poliklinik as poli", 'poli.kd_poli', '=', 'maping_poli.kd_poli_rs')
                        ->where('no_surat', $request->no_surat)
                        ->first(
                            [
                                "bsk.no_surat as no_surat",
                                "rp.no_rkm_medis as no_rkm_medis",
                                "maping_dokter.kd_dokter as kd_dokter",
                                "maping_poli.kd_poli_rs as kd_poli",
                                "maping_dokter.kd_dokter_bpjs as kd_dokter_bpjs",
                                "maping_poli.kd_poli_bpjs as kd_poli_bpjs",
                                "maping_dokter.nm_dokter_bpjs as nm_dokter_bpjs",
                                "maping_poli.nm_poli_bpjs as nm_poli_bpjs",
                                "poli.registrasilama as biaya_registrasi",
                                "p.tgl_lahir as tgl_lahir_pasien",
                                "p.no_rkm_medis as no_rm",
                                "p.no_ktp as no_ktp",
                                "bs.no_kartu as no_kartu",
                                "bs.notelep as no_telp",
                            ]
                        ))
        ) {
            throw ValidationException::withMessages(["no_surat" => "No. surat kontrol tidak ditemukan"]);
        }

        if (
            DB::table('referensi_mobilejkn_bpjs')
               ->where('nomorreferensi', $request->no_surat)
               ->whereNot('status', 'Batal')
               ->exists()
        ) {
            throw ValidationException::withMessages(
                ["no_surat" => "No. surat sudah pernah dikunakan untuk mendaftar"]
            );
        }
        if (
            !($jadwal = DB::table("jadwal")
                ->where('kd_dokter', $surat->kd_dokter)
                ->where('hari_kerja', $this->getNamaHari($tglAntrean))
                ->first(['kuota', 'jam_mulai']))
        ) {
            throw ValidationException::withMessages([
                "tgl_antrean" => "Jadwal dokter tidak di temukan pada tanggal tersebut",
            ]);
        };

        $jumlahAntrean = DB::table("reg_periksa")
            ->where('kd_dokter', $surat->kd_dokter)
            ->where('tgl_registrasi', $tglAntrean)
            ->whereNot('stts', 'Batal')
            ->count();

        if ($jumlahAntrean >= $jadwal->kuota) {
            throw ValidationException::withMessages([
                "tgl_antrean" => "antrean sudah penuh",
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
        //
        // // Generate nobooking
        $prefixBooking = date('Ymd', strtotime($tglAntrean));
        $lastBooking = DB::table('referensi_mobilejkn_bpjs')
            ->where('nobooking', 'like', $prefixBooking . '%')
            ->orderBy('nobooking', 'desc')
            ->first([DB::raw('CONVERT(RIGHT(nobooking,6),signed) as nobooking')]);
        //
        $newNumBooking = ($lastBooking->nobooking ?? 0) + 1;
        $noBooking = $prefixBooking . str_pad($newNumBooking, 6, '0', STR_PAD_LEFT);

        $nomorAntrean = $jumlahAntrean + 1;

        // no_reg (per poli + dokter + tanggal)
        $noReg = DB::table('reg_periksa')
            ->whereDate('tgl_registrasi', $tglAntrean)
            ->where('kd_poli', $request->kd_poli)
            ->where('kd_dokter', $request->kd_dokter)
            ->count() + 1;

        $jamMulai =  substr($jadwal->jam_mulai, 0, 5);

        $estimasiMenit = ($nomorAntrean - 1) * 10;
        $estimasiWaktu = strtotime($tglAntrean . ' ' . $jamMulai) + ($estimasiMenit * 60);

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

        $dataRegPeriksa = [
            'no_reg'         => str_pad($noReg, 3, '0', STR_PAD_LEFT),
            'no_rawat'       => $noRawat,
            'tgl_registrasi' => $tglAntrean->format('Y-m-d'),
            'jam_reg'        => "00:00:00",
            'kd_dokter'      => $surat->kd_dokter,
            'no_rkm_medis'   => $surat->no_rm,
            'kd_poli'        => $surat->kd_poli,
            'p_jawab'        => "-",
            'almt_pj'        => '-',
            'hubunganpj'     => 'KELUARGA',
            'biaya_reg'      => $surat->biaya_registrasi,
            'stts'           => 'Belum',
            'stts_daftar'    => 'Lama',
            'status_lanjut'  => 'Ralan',
            'kd_pj'          => 'BPJ',
            'umurdaftar'     => $umur,
            'sttsumur'       => $sttsumur,
            'status_bayar'   => 'Belum Bayar',
            'status_poli'    => 'Lama',
        ];

        $dataReferensi = [
            'nobooking'        => $noBooking,
            'no_rawat'         => $noRawat,
            'nomorkartu'       => $surat->no_kartu ?? '',
            'nik'              => $surat->no_ktp ?? '',
            'nohp'             => $surat->no_telp ?? '',
            'kodepoli'         => $surat->kd_poli_bpjs,
            'pasienbaru'       => '0',
            'norm'             => $surat->no_rm,
            'tanggalperiksa'   => $tglAntrean,
            'kodedokter'       => $surat->kd_dokter_bpjs,
            'jampraktek'       => $jamMulai,
            'jeniskunjungan'   => '2 (Rujukan Internal)',
            'nomorreferensi'   => $surat->no_surat,
            'nomorantrean'     => $nomorAntrean,
            'angkaantrean'     => $nomorAntrean,
            'estimasidilayani' => $estimasiWaktu,
            'sisakuotajkn'     => $jadwal->kuota - $nomorAntrean,
            'sisakuotanonjkn'  => $jadwal->kuota - $nomorAntrean,
            'kuotajkn'         => $jadwal->kuota,
            'kuotanonjkn'      => $jadwal->kuota,
            'status'           => 'Belum',
            'validasi'         => null,
            'statuskirim'      => 'Belum',
        ];

        DB::transaction(function () use ($dataRegPeriksa, $dataReferensi) {
            DB::table('reg_periksa')->insert($dataRegPeriksa);
            DB::table('referensi_mobilejkn_bpjs')->insert($dataReferensi);
        });

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
                'sisa_kuota'      => $jadwal->kuota - $nomorAntrean,
            ],
        ]);
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
            ->first('kuota');

        if (!$data) {
            throw ValidationException::withMessages([
                "kd_dokter" => "tidak ada jadwal dokter pada tanggal tsb",
            ]);
        }

        $count_regis = DB::table('reg_periksa')
            ->where('tgl_registrasi', $tgl_antrean)
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

    // Method debug tetap dipertahankan
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
}
