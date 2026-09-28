<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipRebateLog extends Model
{
    protected $fillable = [
        'fight_id',
        'player_id',
        'bet_id',
        'vip_tier_id',
        'side',
        'valid_amount',
        'rebate_percent',
        'amount',
        'credited_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_amount' => 'decimal:2',
            'rebate_percent' => 'decimal:2',
            'amount' => 'decimal:2',
            'credited_at' => 'datetime',
        ];
    }

    public function fight(): BelongsTo
    {
        return $this->belongsTo(Fight::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    public function vipTier(): BelongsTo
    {
        return $this->belongsTo(VipTier::class);
    }
}
