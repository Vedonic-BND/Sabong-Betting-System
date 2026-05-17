<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fight extends Model
{
    use HasFactory;

    protected $table = 'fights';

    protected $fillable = [
        'created_by',
        'fight_number',
        'status',
        'meron_status',
        'wala_status',
        'winner',
        'commission_rate',
        'session_date',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
        ];
    }

    // ─── Relationships ───────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bets()
    {
        return $this->hasMany(Bet::class, 'fight_id');
    }

    // ─── Helpers ─────────────────────────────────────

    public function totalBets(): float
    {
        return $this->bets()->sum('amount');
    }

    public function meronTotal(): float
    {
        return $this->bets()->where('side', 'meron')->sum('amount');
    }

    public function walaTotal(): float
    {
        return $this->bets()->where('side', 'wala')->sum('amount');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }
}
