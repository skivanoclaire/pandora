<?php

namespace App\Http\Middleware;

use App\Models\Integrity\AuditTrail;
use Closure;
use Illuminate\Http\Request;

class LogAuditTrail
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log GET requests that return 200 and have an authenticated user
        if ($request->isMethod('GET') && $response->getStatusCode() === 200 && auth()->check()) {
            try {
                $path = $request->path();
                $aksi = $this->resolveAksi($path);
                if ($aksi) {
                    AuditTrail::catat(auth()->id(), $aksi, $path, null, $request->query() ?: null);
                }
            } catch (\Throwable $e) {
                // Don't break the request if audit fails
            }
        }

        return $response;
    }

    private function resolveAksi(string $path): ?string
    {
        return match(true) {
            str_starts_with($path, 'dashboard') => 'lihat_dashboard',
            str_starts_with($path, 'master/') => 'lihat_master_data',
            str_starts_with($path, 'kehadiran/') => 'lihat_kehadiran',
            str_starts_with($path, 'analitik/') => 'lihat_analitik',
            str_starts_with($path, 'sinkronisasi') => 'lihat_sinkronisasi',
            str_starts_with($path, 'integritas') => 'lihat_integritas',
            str_starts_with($path, 'pengaturan') => 'lihat_pengaturan',
            default => null,
        };
    }
}
