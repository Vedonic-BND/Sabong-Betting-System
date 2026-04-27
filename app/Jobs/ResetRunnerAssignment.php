<?php

namespace App\Jobs;

use App\Models\CashRequest;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetRunnerAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cashRequestId;

    public function __construct($cashRequestId)
    {
        $this->cashRequestId = $cashRequestId;
    }

    public function handle(): void
    {
        $cashRequest = CashRequest::find($this->cashRequestId);

        // Only reset if still in approved status (not completed)
        if ($cashRequest && $cashRequest->status === 'approved') {
            $assignedRunner = $cashRequest->runner;

            // Reset the runner assignment
            $cashRequest->update([
                'runner_id' => null,
                'status' => 'pending',
                'approved_at' => null,
                'approved_by' => null,
            ]);

            // Notify the teller that the runner was not available
            Notification::create([
                'user_id' => $cashRequest->teller_id,
                'title' => 'Runner Unavailable',
                'message' => "{$assignedRunner->name} did not complete the request. Requesting another runner.",
                'data' => json_encode([
                    'cash_request_id' => $cashRequest->id,
                    'runner_id' => $assignedRunner->id,
                    'runner_name' => $assignedRunner->name,
                ]),
                'is_read' => false,
            ]);

            // Notify all runners that the request is available again
            $runners = \App\Models\User::where('role', 'runner')
                ->where('id', '!=', $assignedRunner->id)
                ->get();

            foreach ($runners as $runner) {
                Notification::create([
                    'user_id' => $runner->id,
                    'title' => 'Request Available',
                    'message' => "A runner request from {$cashRequest->teller->name} is available.",
                    'data' => json_encode([
                        'cash_request_id' => $cashRequest->id,
                        'teller_id' => $cashRequest->teller_id,
                        'teller_name' => $cashRequest->teller->name,
                        'request_type' => $cashRequest->request_type,
                    ]),
                    'is_read' => false,
                ]);
            }
        }
    }
}
