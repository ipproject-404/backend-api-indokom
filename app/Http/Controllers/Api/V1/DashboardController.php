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
        
        $batasJamMasuk = '08:00:00';

        // 1. Hitung Total Karyawan
        $totalKaryawan = User::count();

        // 2. Hitung Hadir (Total baris absensi hari ini)
        $hadir = Presensi::whereDate('created_at', $today)->count();

        // 3. Hitung Terlambat (LOGIKA BARU)
        // Menghitung absensi hari ini yang jam-nya LEBIH BESAR dari batasJamMasuk
        $terlambat = Presensi::whereDate('jam_masuk', $today)
                             ->whereTime('jam_masuk', '>', $batasJamMasuk)
                             ->count();

        // 4. Hitung Belum Absen
        $belumAbsen = $totalKaryawan - $hadir;
        if ($belumAbsen < 0) $belumAbsen = 0;

        // 5. Data Grafik Mingguan
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