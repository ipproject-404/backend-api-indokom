<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Hitung Total Karyawan (menggunakan tabel users)
        $totalKaryawan = User::count();

        // 2. Hitung Hadir Hari Ini (Asumsi kolom status = 'hadir')
        $hadir = Presensi::whereDate('created_at', $today)
                         ->where('status', 'hadir') 
                         ->count();

        // 3. Hitung Terlambat Hari Ini (Asumsi kolom status = 'terlambat')
        $terlambat = Presensi::whereDate('created_at', $today)
                             ->where('status', 'terlambat')
                             ->count();

        // 4. Hitung Belum Absen
        $belumAbsen = $totalKaryawan - ($hadir + $terlambat);
        if ($belumAbsen < 0) $belumAbsen = 0; // Jaga-jaga jika data aneh

        // 5. (Opsional) Data dummy untuk grafik mingguan agar tidak error di frontend
        // Nanti bisa diganti dengan query Group By tanggal
        $grafikMingguan = [
            ['name' => 'Sen', 'tepat' => $hadir, 'telat' => $terlambat],
            ['name' => 'Sel', 'tepat' => 300, 'telat' => 13],
            ['name' => 'Rab', 'tepat' => 500, 'telat' => 48],
            ['name' => 'Kam', 'tepat' => 200, 'telat' => 39],
            ['name' => 'Jum', 'tepat' => 278, 'telat' => 48],
            ['name' => 'Sab', 'tepat' => 189, 'telat' => 20],
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_karyawan' => $totalKaryawan,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'belum_absen' => $belumAbsen,
                'grafik_mingguan' => $grafikMingguan
            ]
        ], 200);
    }
}