<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// Sesuaikan dengan nama Model absensi di project-mu
// Jika namanya 'Absen', ganti menjadi use App\Models\Absen;
use App\Models\Presensi; 

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        // $request->user() akan otomatis mendeteksi user dari Token yang dikirim
        $user = $request->user();

        // Mengambil data absensi HANYA milik user yang sedang login
        // (Asumsi: tabel absensi punya kolom 'user_id')
        $absensi = Presensi::where('karyawan_id', $user->karyawan_id)
                          ->orderBy('created_at', 'desc')
                          ->take(10) // Ambil 10 data terakhir
                          ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat absensi berhasil diambil',
            'data_user' => $user->name, // Menampilkan nama user yg login
            'data_absensi' => $absensi
        ], 200);
    }
}