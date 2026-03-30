<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'bet_id',
        'gross_payout',
        'commission',
        'net_payout',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gross_payout' => 'decimal:2',
            'commission'   => 'decimal:2',
            'net_payout'   => 'decimal:2',
        ];
    }

    // ─── Relationships ───────────────────────────────

    public function bet()
    {
        return $this->belongsTo(Bet::class, 'bet_id');
    }
}
