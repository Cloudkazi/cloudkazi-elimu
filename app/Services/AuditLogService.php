<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log a platform-level action.
     * Never logs passwords, API keys, or credentials.
     */
    public static function log(
        string $action,
        string $module = 'platform',
        ?string $description = null,
        ?int $schoolId = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        // Strip sensitive keys before logging
        $sensitiveKeys = [
            'password', 'daraja_passkey', 'daraja_consumer_key', 'daraja_consumer_secret',
            'api_key', 'secret', 'token', 'mpesa_passkey',
        ];

        $clean = static function (array $data) use ($sensitiveKeys): array {
            foreach ($sensitiveKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $data[$key] = '[REDACTED]';
                }
            }
            return $data;
        };

        try {
            AuditLog::create([
                'user_id'     => Auth::id(),
                'school_id'   => $schoolId,
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'old_values'  => $oldValues ? $clean($oldValues) : null,
                'new_values'  => $newValues ? $clean($newValues) : null,
                'ip_address'  => Request::ip(),
                'user_agent'  => Request::userAgent(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never break the main flow
            \Log::warning('AuditLog write failed: ' . $e->getMessage());
        }
    }
}
