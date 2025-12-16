<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userA = User::create([
            'name' => 'Buyer One',
            'email' => 'buyer@test.com',
            'password' => 'password123',
            'balance' => '100000.00000000', // $100k
        ]);

        // User B (Seller)
        $userB = User::create([
            'name' => 'Seller One',
            'email' => 'seller@test.com',
            'password' => 'password123',
            'balance' => '0.00000000',
        ]);

        // Give Seller BTC & ETH
        Asset::create([
            'user_id' => $userB->id,
            'symbol' => 'BTC',
            'amount' => '2.00000000',
            'locked_amount' => '0.00000000',
        ]);

        Asset::create([
            'user_id' => $userB->id,
            'symbol' => 'ETH',
            'amount' => '10.00000000',
            'locked_amount' => '0.00000000',
        ]);
    }
}
