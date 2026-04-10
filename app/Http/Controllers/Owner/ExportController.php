<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Fight;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->date_from, fn($q) =>
                $q->whereDate('created_at', '>=', $request->date_from)
            )
            ->when($request->date_to, fn($q) =>
                $q->whereDate('created_at', '<=', $request->date_to)
            )
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'audit-logs-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // headers
            fputcsv($file, [
                'ID',
                'Date & Time',
                'User',
                'Role',
                'Action',
                'Target Type',
                'Target ID',
                'Details',
                'IP Address',
            ]);

            foreach ($logs as $log) {
                $payload = '';
                if ($log->payload) {
                    $parts = [];
                    foreach ($log->payload as $key => $value) {
                        $parts[] = "$key: $value";
                    }
                    $payload = implode(', ', $parts);
                }

                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user->name  ?? '—',
                    $log->user->role  ?? '—',
                    str_replace('_', ' ', $log->action),
                    $log->target_type ?? '—',
                    $log->target_id   ?? '—',
                    $payload,
                    $log->ip_address  ?? '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function fights(Request $request)
    {
        $fights = Fight::with('creator')
            ->when($request->fight_number, fn($q) =>
                $q->where('fight_number', 'like', '%' . $request->fight_number . '%')
            )
            ->when($request->winner, fn($q) =>
                $q->where('winner', 'like', '%' . strtolower($request->winner) . '%')
            )
            ->when($request->date, fn($q) =>
                $q->whereDate('created_at', $request->date)
            )
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'fights-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($fights) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID',
                'Fight Number',
                'Status',
                'Winner',
                'Meron Total',
                'Wala Total',
                'Total Bets',
                'Commission Rate',
                'Commission Amount',
                'Net Pool',
                'Created By',
                'Date & Time',
            ]);

            foreach ($fights as $fight) {
                $meronTotal  = $fight->meronTotal();
                $walaTotal   = $fight->walaTotal();
                $totalPool   = $meronTotal + $walaTotal;
                $commission  = $totalPool * ($fight->commission_rate / 100);
                $netPool     = $totalPool - $commission;

                fputcsv($file, [
                    $fight->id,
                    $fight->fight_number,
                    ucfirst($fight->status),
                    $fight->winner ? ucfirst($fight->winner) : '—',
                    number_format($meronTotal, 2),
                    number_format($walaTotal, 2),
                    number_format($totalPool, 2),
                    $fight->commission_rate . '%',
                    number_format($commission, 2),
                    number_format($netPool, 2),
                    $fight->creator->name ?? '—',
                    $fight->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
