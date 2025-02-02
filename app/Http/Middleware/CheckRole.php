<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!$request->user() || $request->user()->role !== $role) {
            return response()->json([
                'message' => 'Bu işlem için yetkiniz bulunmamaktadır.'
            ], 403);
        }

        return $next($request);
    }
}
