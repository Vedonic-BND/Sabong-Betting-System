<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function log(
        string $action,
        string $targetType = null,
        int $targetId = null,
        array $payload = null
    ): void {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'payload'     => $payload,
            'ip_address'  => Request::ip(),
        ]);
    }
}
