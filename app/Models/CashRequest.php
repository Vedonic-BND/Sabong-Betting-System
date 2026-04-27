<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'teller_id',
        'runner_id',
        'type',           // 'cash_in' or 'cash_out'
        'amount',
        'reason',
        'request_type',   // 'assistance', 'need_cash', 'collect_cash', 'other'
        'custom_message', // For 'other' request type
        'status',         // 'pending', 'approved', 'completed', 'rejected'
        'approved_at',
        'completed_at',
        'approved_by',    // runner_id
        'completed_by',   // runner_id
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'approved_at'  => 'datetime',
            'completed_at' => 'datetime',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function runner()
    {
        return $this->belongsTo(User::class, 'runner_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
