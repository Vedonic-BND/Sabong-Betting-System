<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use App\Models\Fight;
use App\Models\CashRequest;
use Carbon\Carbon;

class ExportEodReport extends Command
{
    protected $signature = 'eod:report';
    protected $description = 'Generate End-of-Day audit report and save to storage';

    public function handle()
    {
        $date = now()->format('Y-m-d');
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $this->info("📊 Starting EOD Report generation for $date at $timestamp");

        try {
            // Create EOD reports directory if it doesn't exist
            $reportsDir = storage_path('eod-reports');
            if (!is_dir($reportsDir)) {
                mkdir($reportsDir, 0755, true);
            }

            // Get today's data
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();

            // Fetch data for today
            $auditLogs = AuditLog::with('user')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->orderBy('created_at', 'asc')
                ->get();

            $fights = Fight::with('creator')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->orderBy('created_at', 'asc')
                ->get();

            $transactions = CashRequest::with(['runner', 'teller'])
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->orderBy('created_at', 'asc')
                ->get();

            // Generate filenames
            $auditFilename = "$reportsDir/audit-logs-eod-$date.csv";
            $fightsFilename = "$reportsDir/fights-eod-$date.csv";
            $transactionsFilename = "$reportsDir/transactions-eod-$date.csv";

            // Generate Audit Logs Report
            $this->generateAuditLogsReport($auditLogs, $auditFilename);
            
            // Generate Fights Report
            $this->generateFightsReport($fights, $fightsFilename);
            
            // Generate Transactions Report
            $this->generateTransactionsReport($transactions, $transactionsFilename);

            // Generate Summary Report
            $summaryFilename = "$reportsDir/summary-eod-$date.txt";
            $this->generateSummaryReport($auditLogs, $fights, $transactions, $summaryFilename, $date);

            $this->info("✅ EOD Report generated successfully for $date");
            $this->info("📁 Audit Logs: $auditFilename");
            $this->info("📁 Fights: $fightsFilename");
            $this->info("📁 Transactions: $transactionsFilename");
            $this->info("📁 Summary: $summaryFilename");

            // Log the action
            \Log::info("✅ EOD Report generated successfully for $date", [
                'audit_logs_count' => $auditLogs->count(),
                'fights_count' => $fights->count(),
                'transactions_count' => $transactions->count(),
            ]);

        } catch (\Exception $e) {
            $this->error("❌ Failed to generate EOD Report: " . $e->getMessage());
            \Log::error("❌ EOD Report generation failed: " . $e->getMessage());
        }
    }

    private function generateAuditLogsReport($logs, $filename)
    {
        $file = fopen($filename, 'w');
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
                $log->user->name ?? '—',
                $log->user->role ?? '—',
                str_replace('_', ' ', $log->action),
                $log->target_type ?? '—',
                $log->target_id ?? '—',
                $payload,
                $log->ip_address ?? '—',
            ]);
        }

        fclose($file);
    }

    private function generateFightsReport($fights, $filename)
    {
        $file = fopen($filename, 'w');
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
            $meronTotal = $fight->meronTotal();
            $walaTotal = $fight->walaTotal();
            $totalPool = $meronTotal + $walaTotal;
            $commission = $totalPool * ($fight->commission_rate / 100);
            $netPool = $totalPool - $commission;

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
    }

    private function generateTransactionsReport($transactions, $filename)
    {
        $file = fopen($filename, 'w');
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($file, [
            'ID',
            'Runner',
            'Teller',
            'Type',
            'Amount',
            'Status',
            'Date & Time',
        ]);

        foreach ($transactions as $transaction) {
            fputcsv($file, [
                $transaction->id,
                $transaction->runner?->name ?? '—',
                $transaction->teller?->name ?? '—',
                ucfirst(str_replace('_', ' ', $transaction->type)),
                number_format($transaction->amount, 2),
                ucfirst($transaction->status),
                $transaction->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($file);
    }

    private function generateSummaryReport($auditLogs, $fights, $transactions, $filename, $date)
    {
        $totalBets = $fights->sum(function($fight) {
            return $fight->meronTotal() + $fight->walaTotal();
        });

        $totalCommission = $fights->sum(function($fight) {
            $meronTotal = $fight->meronTotal();
            $walaTotal = $fight->walaTotal();
            $totalPool = $meronTotal + $walaTotal;
            return $totalPool * ($fight->commission_rate / 100);
        });

        $cashIn = $transactions->where('type', 'cash_in')->sum('amount');
        $cashOut = $transactions->where('type', 'cash_out')->sum('amount');

        $summary = "═══════════════════════════════════════════════════════════════\n";
        $summary .= "                    END-OF-DAY REPORT\n";
        $summary .= "                    $date\n";
        $summary .= "═══════════════════════════════════════════════════════════════\n\n";

        $summary .= "FIGHTS SUMMARY\n";
        $summary .= "─────────────────────────────────────────────────────────────\n";
        $summary .= "Total Fights: " . $fights->count() . "\n";
        $summary .= "Total Bets Collected: ₱" . number_format($totalBets, 2) . "\n";
        $summary .= "Total Commission: ₱" . number_format($totalCommission, 2) . "\n";
        $summary .= "Net Pool: ₱" . number_format($totalBets - $totalCommission, 2) . "\n\n";

        $summary .= "RUNNER TRANSACTIONS SUMMARY\n";
        $summary .= "─────────────────────────────────────────────────────────────\n";
        $summary .= "Total Cash Provided to Tellers: ₱" . number_format($cashIn, 2) . "\n";
        $summary .= "Total Cash Collected from Tellers: ₱" . number_format($cashOut, 2) . "\n";
        $summary .= "Net Cash Movement: ₱" . number_format($cashIn - $cashOut, 2) . "\n";
        $summary .= "Total Transactions: " . $transactions->count() . "\n\n";

        $summary .= "AUDIT LOGS SUMMARY\n";
        $summary .= "─────────────────────────────────────────────────────────────\n";
        $summary .= "Total Actions Recorded: " . $auditLogs->count() . "\n";
        $summary .= "Users Active: " . $auditLogs->pluck('user_id')->unique()->count() . "\n\n";

        $summary .= "═══════════════════════════════════════════════════════════════\n";
        $summary .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $summary .= "═══════════════════════════════════════════════════════════════\n";

        file_put_contents($filename, $summary);
    }
}
