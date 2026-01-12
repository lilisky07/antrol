<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\petugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string'
        ]);

        $petugas = petugas::where('nip', $request->nip)->first();

        if (!$petugas) {
            return response()->json([
                'success' => false,
                'message' => 'NIP petugas tidak ditemukan'
            ], 401);
        }

        // Login berhasil (tanpa password)
        $token = $petugas->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'petugas' => [
                'id' => $petugas->id,
                'nama' => $petugas->nama,
                'nip' => $petugas->nip,
                'poli' => $petugas->poli
            ],
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'petugas' => $request->user()
        ]);
    }
}

