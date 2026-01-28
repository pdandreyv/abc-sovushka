<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isMaintenanceEnabled()) {
            return $next($request);
        }

        if ($this->isAdmin($request->user())) {
            return $next($request);
        }

        if ($this->isAllowedDuringMaintenance($request)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }

    private function isMaintenanceEnabled(): bool
    {
        $configPath = base_path('public/abc/_config.php');
        if (!is_file($configPath)) {
            return false;
        }

        $config = [];
        try {
            include $configPath;
        } catch (\Throwable $e) {
            return false;
        }

        $value = $config['dummy'] ?? null;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = strtolower((string) $user->role);
        return in_array($role, ['admin', 'administrator', 'superadmin', 'owner'], true);
    }

    private function isAllowedDuringMaintenance(Request $request): bool
    {
        return $request->is('login')
            || $request->is('register')
            || $request->is('auth/*')
            || $request->is('logout')
            || $request->is('up');
    }
}
