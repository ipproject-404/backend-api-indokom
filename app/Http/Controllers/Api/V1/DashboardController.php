<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Presensi; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use KarimTao\ServerStatus\ServerMetrics;
use Illuminate\Support\Facades\DB;

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

    public function serverStats()
{
    // 1. Mengambil metrik server (CPU, RAM, Disk, Uptime)
    $snapshot = app(ServerMetrics::class)->read();

    $cpuName = 'Unknown';
    $osName = 'Unknown';

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Menjalankan perintah PowerShell dari PHP
            $cpuName = trim(shell_exec('powershell -Command "(Get-CimInstance Win32_Processor).Name"'));
            $osName = trim(shell_exec('powershell -Command "(Get-CimInstance Win32_OperatingSystem).Caption"'));
            
            // Jika kosong (karena shell_exec diblokir), beri fallback
            if (!$cpuName) $cpuName = 'Windows Processor';
            if (!$osName) $osName = 'Windows OS';
        }

    // 2. Mengukur waktu respon API (Latency)
    $start = microtime(true);
    sleep(2); 
    DB::connection()->getPdo(); // Cek koneksi database
    $latency = round((microtime(true) - $start) * 1000); // dalam milidetik (ms)

    return response()->json([
        'status' => 'success',
        'data' => [
            'cpu' => round($snapshot->cpu, 2),
            'cpu_cores' => $snapshot->cores,
            'cpu_name' => $cpuName, // <-- Tambahan baru
            'os_name' => $osName, 
            'ram_used' => round($snapshot->memoryUsed / 1024 / 1024 / 1024, 2), // GB
            'ram_total' => round($snapshot->memoryTotal / 1024 / 1024 / 1024, 2), // GB
            'ram_percent' => round(($snapshot->memoryUsed / $snapshot->memoryTotal) * 100, 2),
            'disk_used' => round($snapshot->diskUsed / 1024 / 1024 / 1024, 2), // GB
            'disk_total' => round($snapshot->diskTotal / 1024 / 1024 / 1024, 2), // GB
            'disk_percent' => round(($snapshot->diskUsed / $snapshot->diskTotal) * 100, 2),
            'uptime' => floor($snapshot->uptime / 3600) . ' jam',
            'db_status' => 'Connected',
            'api_latency_ms' => $latency,
            'server_time' => Carbon::now()->format('Y-m-d H:i:s'),
        ]
    ], 200);
}
}