<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbsensiApmController extends Controller
{
    public function metrics()
    {
        $today = Carbon::today();

        // 1. Hitung Traffic Peak (RPS per jam hari ini)
        // Kita ambil log yang endpoint-nya berhubungan dengan absensi
        $trafficData = ApiLog::select(
            DB::raw("DATE_FORMAT(created_at, '%H:%i') as time"), // Format Jam:Menit
            DB::raw('COUNT(*) as requests') // Ambil total request mentahnya
        )
        ->whereDate('created_at', $today)
        ->where('endpoint', 'LIKE', '%absensi%') // Filter hanya endpoint absensi
        ->groupBy('time')
        ->orderBy('time')
        ->get();
        // ->map(function ($item) {
        //     return [
        //         'time' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',
        //         'rps' => round($item->total_requests / 3600, 2) // Rata-rata per detik di jam tersebut
        //     ];
        // });

        // 2. HTTP Status Breakdown
        $statusCodes = ApiLog::select('status_code', DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', $today)
            ->groupBy('status_code')
            ->get();
            
        $success = $statusCodes->where('status_code', 200)->sum('count');
        $clientError = $statusCodes->whereBetween('status_code', [400, 499])->sum('count');
        $serverError = $statusCodes->whereBetween('status_code', [500, 599])->sum('count');
        
        $totalRequests = $success + $clientError + $serverError;
        
        $httpStatusData = [
            ['name' => '2xx OK', 'value' => $totalRequests > 0 ? round(($success / $totalRequests) * 100) : 0, 'fill' => '#10b981'],
            ['name' => '4xx Client', 'value' => $totalRequests > 0 ? round(($clientError / $totalRequests) * 100) : 0, 'fill' => '#f59e0b'],
            ['name' => '5xx Server', 'value' => $totalRequests > 0 ? round(($serverError / $totalRequests) * 100) : 0, 'fill' => '#ef4444'],
        ];

     $apiResponseTimes = ApiLog::select(
            DB::raw("DATE_FORMAT(created_at, '%H:%i') as time"),
            DB::raw('AVG(response_time) as ms')
        )
        ->whereDate('created_at', $today)
        ->whereNotNull('response_time')
        ->where('endpoint', 'LIKE', '%absensi%')
        ->groupBy('time')
        ->orderBy('time')
        ->get()
        ->map(function($item) {
            return ['time' => $item->time, 'ms' => round($item->ms)];
        });

        // Rata-rata keseluruhan hari ini
        $avgResponseTime = round(ApiLog::whereDate('created_at', $today)->avg('response_time') ?? 0);

        return response()->json([
            'status' => 'success',
            'data' => [
                'traffic_peak' => $trafficData,
                'http_status' => $httpStatusData,
                'api_response_time' => $apiResponseTimes, // <-- Data baru
                'avg_response_time' => $avgResponseTime    // <-- Data baru
            ]
        ], 200);
    }
}