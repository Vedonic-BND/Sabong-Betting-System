<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\CashRequest;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = CashRequest::with(['runner', 'teller'])
            ->when($request->runner_name, fn($q) =>
                $q->whereHas('runner', fn($query) =>
                    $query->where('name', 'like', '%' . $request->runner_name . '%')
                )
            )
            ->when($request->teller_name, fn($q) =>
                $q->whereHas('teller', fn($query) =>
                    $query->where('name', 'like', '%' . $request->teller_name . '%')
                )
            )
            ->when($request->type, fn($q) =>
                $q->where('type', $request->type)
            )
            ->when($request->date, fn($q) =>
                $q->whereDate('created_at', $request->date)
            )
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('owner.transactions.index', compact('transactions'));
    }

    public function export(Request $request)
    {
        $query = CashRequest::with(['runner', 'teller']);

        // Apply same filters as index
        if ($request->runner_name) {
            $query->whereHas('runner', fn($q) =>
                $q->where('name', 'like', '%' . $request->runner_name . '%')
            );
        }

        if ($request->teller_name) {
            $query->whereHas('teller', fn($q) =>
                $q->where('name', 'like', '%' . $request->teller_name . '%')
            );
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $fileName = 'runner_transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $columns = ['ID', 'Runner', 'Teller', 'Type', 'Amount', 'Status', 'Date & Time'];

        $callback = function () use ($transactions, $columns) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write header
            fputcsv($file, $columns);

            // Write data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->runner?->name ?? '—',
                    $transaction->teller?->name ?? '—',
                    ucfirst(str_replace('_', ' ', $transaction->type)),
                    number_format($transaction->amount, 2),
                    ucfirst($transaction->status),
                    $transaction->created_at->format('M d, Y H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
