<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Bet extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'fight_id',
        'teller_id',
        'reference',
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

    // ─── Auto generate reference ──────────────────────────

    protected static function booted(): void
    {
        static::creating(function ($bet) {
            $bet->reference = strtoupper(Str::random(3)) . '-' . random_int(100000, 999999);
        });
    }

    // ─── Relationships ────────────────────────────────────

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
