<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!$request->user() || $request->user()->role !== $role) {
            
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak! Anda tidak memiliki izin untuk halaman ini.',
                'data'    => null
            ], 403);
        }

        return $next($request);
    }
}