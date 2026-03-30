<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'fight_id',
        'teller_id',
        'side',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────

    public function fight()
    {
        return $this->belongsTo(Fight::class, 'fight_id');
    }

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function payout()
    {
        return $this->hasOne(Payout::class, 'bet_id');
    }
}
