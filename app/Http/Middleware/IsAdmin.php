<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
        public function handle(Request $request, Closure $next): Response
    {
        // Jika user belum login ATAU role-nya bukan admin, tolak!
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Anda bukan Admin.'
            ], 403); // 403 Forbidden
        }

        return $next($request);
    }
}
