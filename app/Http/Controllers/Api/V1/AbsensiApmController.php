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

        // 1. Traffic Data (Kode lama biarkan)
        $trafficData = ApiLog::select(
            DB::raw("DATE_FORMAT(created_at, '%H:%i') as time"),
            DB::raw('COUNT(*) as requests')
        )
        ->whereDate('created_at', $today)
        ->where('endpoint', 'LIKE', '%absensi%') 
        ->groupBy('time')
        ->orderBy('time')
        ->get();

        // 2. HTTP Status (Kode lama biarkan)
        // ... (biarkan query statusCodes yang lama) ...

        // 3. API Response Time (BARU)
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