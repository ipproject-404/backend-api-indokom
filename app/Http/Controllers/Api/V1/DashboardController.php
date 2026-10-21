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
        $snapshot = app(ServerMetrics::class)->read();

        $cpuName = 'Unknown';
        $osName = 'Unknown';
        $netRx = 0; 
        $netTx = 0; 

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cpuName = trim(shell_exec('powershell -Command "(Get-CimInstance Win32_Processor).Name"'));
            $osName = trim(shell_exec('powershell -Command "(Get-CimInstance Win32_OperatingSystem).Caption"'));
            $netRx = (float) trim(shell_exec('powershell -Command "(Get-NetAdapterStatistics | Measure-Object -Property ReceivedBytes -Sum).Sum"'));
            $netTx = (float) trim(shell_exec('powershell -Command "(Get-NetAdapterStatistics | Measure-Object -Property SentBytes -Sum).Sum"'));
            if (!$cpuName) $cpuName = 'Windows Processor';
            if (!$osName) $osName = 'Windows OS';
        }

                $cpuTemp = null;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $tempCommand = 'powershell -Command "(Get-CimInstance -Namespace root/wmi -ClassName MSAcpi_ThermalZoneTemperature | Select-Object -First 1).CurrentTemperature"';
            $rawTemp = trim(shell_exec($tempCommand));
            
            if (is_numeric($rawTemp) && $rawTemp > 0) {
                $cpuTemp = round(($rawTemp / 10) - 273.15, 1); // Konversi ke Celsius
            }
        }


        // ==========================================
        // 1. FORMAT UPTIME (Hari, Jam, Menit, Detik)
        // ==========================================
        $uptimeSeconds = $snapshot->uptime;
        $days = floor($uptimeSeconds / 86400);
        $hours = floor(($uptimeSeconds % 86400) / 3600);
        $minutes = floor(($uptimeSeconds % 3600) / 60);
        $seconds = $uptimeSeconds % 60;
        
        $uptimeString = "";
        if ($days > 0) $uptimeString .= "{$days} Hari, ";
        if ($hours > 0) $uptimeString .= "{$hours} Jam, ";
        $uptimeString .= "{$minutes} Menit, {$seconds} Detik";
        if ($days == 0 && $hours == 0 && $minutes == 0) $uptimeString = "{$seconds} Detik";

        // ==========================================
        // 2. INFO JARINGAN (IP Lokal, IP Publik, Status)
        // ==========================================
        $localIp = gethostbyname(gethostname()); // Mendapatkan IP Lokal
        
        // Mendapatkan IP Publik (dibatasi 2 detik agar tidak lemot jika internet mati)
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $publicIp = @file_get_contents('https://api.ipify.org', false, $ctx);
        if (!$publicIp) $publicIp = 'Offline';

        // Cek koneksi ke Google DNS (8.8.8.8)
        $isOnline = @fsockopen("8.8.8.8", 53, $errno, $errstr, 1) ? 'Online' : 'Offline';

        // 3. Mengukur Latency API
        $start = microtime(true);
        DB::connection()->getPdo(); 
        $latency = round((microtime(true) - $start) * 1000); 

        return response()->json([
            'status' => 'success',
            'data' => [
                'cpu' => round($snapshot->cpu, 2),
                'cpu_cores' => $snapshot->cores,
                'cpu_name' => $cpuName,
                'os_name' => $osName,
                'cpu_temp' => $cpuTemp,
                'ram_used' => round($snapshot->memoryUsed / 1024 / 1024 / 1024, 2),
                'ram_total' => round($snapshot->memoryTotal / 1024 / 1024 / 1024, 2),
                'ram_percent' => round(($snapshot->memoryUsed / $snapshot->memoryTotal) * 100, 2),
                'disk_used' => round($snapshot->diskUsed / 1024 / 1024 / 1024, 2),
                'disk_total' => round($snapshot->diskTotal / 1024 / 1024 / 1024, 2),
                'disk_percent' => round(($snapshot->diskUsed / $snapshot->diskTotal) * 100, 2),
                'net_rx_bytes' => $netRx, // <-- Tambahan
                'net_tx_bytes' => $netTx, // <-- Tambahan
                'uptime' => $uptimeString, // <-- Format baru
                'local_ip' => $localIp,    // <-- Data baru
                'public_ip' => $publicIp,  // <-- Data baru
                'net_status' => $isOnline, // <-- Data baru
                'db_status' => 'Connected',
                'api_latency_ms' => $latency,
                'server_time' => Carbon::now()->format('Y-m-d H:i:s'),
            ]
        ], 200);
    }

}