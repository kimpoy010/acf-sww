<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VipTier extends Model
{
    protected $fillable = ['name', 'min_valid_bets', 'max_valid_bets', 'rebate_percent'];

    protected function casts(): array
    {
        return [
            'min_valid_bets' => 'decimal:2',
            'max_valid_bets' => 'decimal:2',
            'rebate_percent' => 'decimal:2',
        ];
    }

    /**
     * The tier a given lifetime-valid-bets total currently qualifies for —
     * the highest-threshold tier whose min_valid_bets it clears, or null
     * if it hasn't reached even the lowest tier yet. A total past every
     * tier's max still resolves to the top tier rather than falling
     * through to none.
     */
    public static function forValidBets(float $total): ?self
    {
        return static::where('min_valid_bets', '<=', $total)
            ->orderByDesc('min_valid_bets')
            ->first();
    }

    /**
     * The next tier up from this one (by threshold), or null if this is
     * already the top tier — used to show a player how much further
     * wagering stands between them and their next rebate rate.
     */
    public function next(): ?self
    {
        return static::where('min_valid_bets', '>', $this->min_valid_bets)
            ->orderBy('min_valid_bets')
            ->first();
    }
}
