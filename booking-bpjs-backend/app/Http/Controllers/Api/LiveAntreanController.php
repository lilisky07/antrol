<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LiveAntreanController extends Controller
{
    // GANTI DENGAN URL API BRIDGE SIMRS LIVE RS GLADISH
    private $apiUrl = 'http://simrs.rsgladish.com/api-bpjsfktl/'; // CONTOH

    // Credential 
    private $username = 'USERNAME_BPJS_RS'; // dari $akunbpjs['user']
    private $password = 'PASSWORD_BPJS_RS'; // dari $akunbpjs['pass']

    public function list(Request $request)
    {
        try {
            // 1. Ambil token dulu dari endpoint auth
            $authResponse = Http::asForm()->post($this->apiUrl . 'auth', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($authResponse->failed() || $authResponse['metadata']['code'] != 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal autentikasi ke SIMRS/BPJS: ' . ($authResponse['metadata']['message'] ?? 'Unknown error')
                ], 500);
            }

            $token = $authResponse['response']['token'];

            // 2. Ambil list rencana kontrol pasca ranap (endpoint KHANZA standar)
            $response = Http::withHeaders([
                'x-username' => $this->username,
                'x-token' => $token,
            ])->post($this->apiUrl . 'rencanakontrol', [ 
                'bulan' => date('m'), 
                'tahun' => date('Y'), 
                
            ]);

            $data = $response->json();

            if ($data['metadata']['code'] != 200) {
                return response()->json([
                    'success' => false,
                    'message' => $data['metadata']['message'] ?? 'Gagal ambil data rencana kontrol'
                ], 500);
            }

            $list = $data['response']['list'] ?? [];

            // Format data sesuai kebutuhan app kamu
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
                'last_page' => 1 
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error koneksi ke SIMRS live: ' . $e->getMessage()
            ], 500);
        }
    }

    // Endpoint ini bisa kamu tambah nanti untuk ambil antrean
    public function ambilAntrean(Request $request)
    {
        // Logika ambil antrean (POST ke /antrean di API bridge)
        // ... (nanti kita tambah kalau sudah jalan list dulu)
    }
}