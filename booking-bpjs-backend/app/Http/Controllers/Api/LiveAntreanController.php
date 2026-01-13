<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SimrsLiveAntreanController extends Controller
{
    // GANTI DENGAN URL API BRIDGE SIMRS LIVE RS GLADISH (minta RS kasih ini)
    private $apiUrl = 'http://simrs.rsgladish.com/api-bpjsfktl/'; // CONTOH SAJA

    // Credential BPJS dari conf.php SIMRS (minta RS kasih username/password atau token)
    private $username = 'USERNAME_DARI_RS'; // dari tabel password_asuransi
    private $password = 'PASSWORD_DARI_RS';

    public function listRencanaKontrol(Request $request)
    {
        try {
            // 1. Ambil token dari endpoint auth bridge
            $authResponse = Http::asForm()->post($this->apiUrl . 'auth', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($authResponse->failed() || !isset($authResponse['response']['token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal autentikasi ke SIMRS/BPJS bridge: ' . ($authResponse['metadata']['message'] ?? 'Unknown error')
                ], 500);
            }

            $token = $authResponse['response']['token'];

            // 2. Ambil list rencana kontrol pasca ranap (endpoint standar KHANZA)
            $response = Http::withHeaders([
                'x-username' => $this->username,
                'x-token' => $token,
            ])->post($this->apiUrl . 'rencanakontrol', [ // ini endpoint untuk list surat kontrol
                'bulan' => date('m'), // bulan sekarang
                'tahun' => date('Y'), // tahun sekarang
            ]);

            $data = $response->json();

            if (!isset($data['metadata']['code']) || $data['metadata']['code'] != 200) {
                return response()->json([
                    'success' => false,
                    'message' => $data['metadata']['message'] ?? 'Gagal ambil data rencana kontrol dari SIMRS'
                ], 500);
            }

            $list = $data['response']['list'] ?? [];

            // Format data sesuai struktur app kamu
            $antrean = array_map(function($item) {
                return [
                    'no_rm' => $item['norm'] ?? '-',
                    'nama' => $item['namapasien'] ?? '-',
                    'tgl_surat' => $item['tglrencanakontrol'] ?? '-',
                    'poli' => $item['namapoli'] ?? '-',
                    'dokter' => $item['namadokter'] ?? '-',
                    'status' => 'Belum Booking',
                    'kode_booking' => $item['nosuratkontrol'] ?? null,
                    'nomor_antrean' => null,
                    'sisa_kuota' => 0
                ];
            }, $list);

            return response()->json([
                'success' => true,
                'data' => $antrean,
                'total' => count($antrean),
                'current_page' => 1,
                'last_page' => 1 // sesuaikan kalau API support pagination
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error koneksi ke SIMRS live: ' . $e->getMessage()
            ], 500);
        }
    }
}