<?php

namespace Database\Seeders;

use App\Models\VipTier;
use Illuminate\Database\Seeder;

class VipTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'VIP', 'min_valid_bets' => 5000, 'max_valid_bets' => 999999.99, 'rebate_percent' => 0.5],
            ['name' => 'VIP 1', 'min_valid_bets' => 1000000, 'max_valid_bets' => 4999999.99, 'rebate_percent' => 0.7],
            ['name' => 'VIP 2', 'min_valid_bets' => 5000000, 'max_valid_bets' => 9999999.99, 'rebate_percent' => 0.8],
            ['name' => 'VIP 3', 'min_valid_bets' => 10000000, 'max_valid_bets' => 19999999.99, 'rebate_percent' => 0.9],
            ['name' => 'SUPER VIP', 'min_valid_bets' => 20000000, 'max_valid_bets' => 50000000, 'rebate_percent' => 1.0],
        ];

        foreach ($tiers as $tier) {
            VipTier::firstOrCreate(['name' => $tier['name']], [
                'min_valid_bets' => $tier['min_valid_bets'],
                'max_valid_bets' => $tier['max_valid_bets'],
                'rebate_percent' => $tier['rebate_percent'],
            ]);
        }
    }
}
