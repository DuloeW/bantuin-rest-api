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
                'message' => 'Access denied! You do not have permission for this resource.',
                'data'    => null
            ], 403);
        }

        return $next($request);
    }
}