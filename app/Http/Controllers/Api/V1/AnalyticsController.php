<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VisitorLog;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function onlineVisitors()
    {
        // Hitung pengunjung unik yang aktif dalam 5 menit terakhir
        $fiveMinutesAgo = Carbon::now()->subMinutes(5);
        
        $activeVisitors = VisitorLog::where('last_seen', '>=', $fiveMinutesAgo);
        
        $totalOnline = $activeVisitors->count();

        // Ambil 5 "pengunjung" dengan request terbanyak hari ini (indikasi spam)
        $topVisitors = VisitorLog::orderBy('request_count', 'desc')
            ->take(5)
            ->get(['hashed_id', 'request_count', 'last_seen'])
            ->map(function ($visitor) {
                // Tampilkan 8 karakter pertama hash saja agar tidak terlalu panjang
                $visitor->hashed_id = substr($visitor->hashed_id, 0, 8) . '...';
                return $visitor;
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_online' => $totalOnline,
                'top_visitors' => $topVisitors
            ]
        ], 200);
    }
}