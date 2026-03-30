<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')
            ->when(request('action'), function ($query) {
                $query->where('action', 'like', '%' . request('action') . '%');
            })
            ->when(request('user'), function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('username', 'like', '%' . request('user') . '%');
                });
            })
            ->when(request('date'), function ($query) {
                $query->whereDate('created_at', request('date'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('owner.audit-logs.index', compact('logs'));
    }
}
