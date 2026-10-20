<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\VisitorLog;
use Carbon\Carbon;

class TrackVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Dapatkan IP Asli (di balik Cloudflare)
        $ip = $request->header('CF-Connecting-IP') ?? $request->ip();
        $userAgent = $request->userAgent() ?? 'Unknown';

        // 2. Buat Hash Anonim (IP + UA + Tanggal + App Key)
        // Tanggal digunakan agar hash berubah setiap hari (privasi ekstra)
        $salt = Carbon::today()->toDateString();
        $hashedId = hash('sha256', $ip . $userAgent . $salt . config('app.key'));

        // 3. Catat atau Update ke Database
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

        $response = $next($request);

        // Catat Log API (untuk APM)
        \App\Models\ApiLog::create([
            'endpoint' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'hashed_ip' => $hashedId
        ]);

        return $response;
    }
}