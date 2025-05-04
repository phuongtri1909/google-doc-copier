<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            // Admins bypass license check
            return $next($request);
        }
        
        if (!Auth::check() || !Auth::user()->hasValidLicense()) {
            return redirect()->route('license.verify')
                ->with('error', 'Bạn cần license key hợp lệ để truy cập chức năng này.');
        }
        
        return $next($request);
    }
}