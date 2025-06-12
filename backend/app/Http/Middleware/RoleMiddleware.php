<?php
// File location: backend/app/Http/Middleware/RoleMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Kiểm tra user đã đăng nhập chưa
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Kiểm tra role của user
        $userRole = $request->user()->role;
        
        // Nếu user có role trong danh sách cho phép
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // Nếu không có quyền
        return response()->json([
            'success' => false,
            'message' => 'Bạn không có quyền truy cập tài nguyên này',
            'required_roles' => $roles,
            'your_role' => $userRole
        ], 403);
    }
}