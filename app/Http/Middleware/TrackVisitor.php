<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\VisitorLog;
use App\Models\ApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TrackVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. FILTER: Jangan catat request CORS (OPTIONS) atau request non-API
        if ($request->isMethod('OPTIONS') || !str_starts_with($request->path(), 'v1/')) {
            return $next($request);
        }

        // 2. Dapatkan IP Asli & buat Hash Anonim
        $ip = $request->header('CF-Connecting-IP') ?? $request->ip();
        $userAgent = $request->userAgent() ?? 'Unknown';
        $salt = Carbon::today()->toDateString();
        $hashedId = hash('sha256', $ip . $userAgent . $salt . config('app.key'));

        // 3. CACHE: Gunakan Cache untuk Live Visitor (TIDAK menyentuh MySQL)
        $cacheKey = 'visitor_' . $hashedId;
        $visitorData = Cache::get($cacheKey, ['count' => 0, 'last_seen' => now()->timestamp]);
        
        $visitorData['count']++;
        $visitorData['last_seen'] = now()->timestamp;
        
        // Simpan di cache selama 5 menit (300 detik)
       // Cache::put($cacheKey, $visitorData, 300); 

        // Catat waktu mulai
        $startTime = microtime(true);

        // Jalankan request (Biarkan user mendapat respons web secepat mungkin)
        $response = $next($request);

        // Hitung durasi
        $responseTime = round((microtime(true) - $startTime) * 1000);

                // 4. QUEUE: Lempar SEMUA tugas catat log (API & Visitor) ke background
        dispatch(function () use ($request, $response, $hashedId, $responseTime) {
            // Catat API Log
            ApiLog::create([
                'endpoint' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'hashed_ip' => $hashedId,
                'response_time' => $responseTime,
            ]);

            // Catat Visitor Log (Untuk halaman Live Traffic di Next.js)
            $visitor = VisitorLog::where('hashed_id', $hashedId)->first();
            if ($visitor) {
                $visitor->increment('request_count');
                $visitor->update(['last_seen' => Carbon::now()]);
            } else {
                VisitorLog::create([
                    'hashed_id' => $hashedId,
                    'request_count' => 1,
                    'last_seen' => Carbon::now()
                ]);
            }
        })->afterResponse();

        return $response;
    }
}