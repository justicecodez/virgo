<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createOrder(User $user, string $symbol, string $side, float $price, float $amount)
    {
        try {
            return DB::transaction(function () use ($user, $symbol, $side, $price, $amount) {

                if ($side === 'buy') {
                    return $this->handleBuy($user, $symbol, $price, $amount);
                } else {
                    return $this->handleSell($user, $symbol, $price, $amount);
                }
            }, 3); // 3 = number of times to retry if deadlock occurs

        } catch (Exception $e) {
            // Log error for debugging
            Log::error('Order creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'symbol' => $symbol,
                'side' => $side,
                'price' => $price,
                'amount' => $amount
            ]);

            // Throw clean exception to controller
            throw new Exception($e->getMessage());
        }
    }

    protected function handleBuy(User $user, string $symbol, float $price, float $amount)
    {
        $totalCost = bcmul($price, $amount, 8); // precise decimal multiplication

        // Lock user row to prevent race conditions
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        if ($user->balance < $totalCost) {
            throw new Exception('Insufficient USD balance');
        }

        // Deduct balance
        $user->balance -= $totalCost;
        $user->save();

        // Create order
        return Order::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => 'buy',
            'price' => $price,
            'amount' => $amount,
            'status' => 1, // open
        ]);
    }

    protected function handleSell(User $user, string $symbol, float $price, float $amount)
    {
        // Lock asset row
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();

        if (!$asset || $asset->amount < $amount) {
            throw new Exception('Insufficient asset balance');
        }

        // Move amount to locked
        $asset->amount -= $amount;
        $asset->locked_amount += $amount;
        $asset->save();

        // Create order
        return Order::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => 'sell',
            'price' => $price,
            'amount' => $amount,
            'status' => 1, // open
        ]);
    }

    public function matchOrder(Order $order)
    {
        try {
            return DB::transaction(function () use ($order) {

                if ($order->side === 'buy') {
                    // Find first SELL order that matches
                    $sellOrder = Order::where('symbol', $order->symbol)
                        ->where('side', 'sell')
                        ->where('status', 1)
                        ->where('price', '<=', $order->price)
                        ->orderBy('created_at')
                        ->lockForUpdate()
                        ->first();

                    if (!$sellOrder) {
                        return null; // No match
                    }

                    $this->executeTrade($order, $sellOrder);
                } else {
                    // SELL side
                    $buyOrder = Order::where('symbol', $order->symbol)
                        ->where('side', 'buy')
                        ->where('status', 1)
                        ->where('price', '>=', $order->price)
                        ->orderBy('created_at')
                        ->lockForUpdate()
                        ->first();

                    if (!$buyOrder) {
                        return null; // No match
                    }

                    $this->executeTrade($buyOrder, $order);
                }
            }, 3); // retry 3 times if deadlock
        } catch (\Exception $e) {
            Log::error('Matching failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'symbol' => $order->symbol,
            ]);
            throw new \Exception($e->getMessage());
        }
    }

    protected function executeTrade(Order $buyOrder, Order $sellOrder)
    {
        $symbol = $buyOrder->symbol;
        $amount = $sellOrder->amount; // full match only
        $price = $sellOrder->price; // use seller's price

        $usdValue = bcmul($amount, $price, 8);
        $commission = bcmul($usdValue, 0.015, 8); // 1.5% fee

        // Update buyer asset
        $buyerAsset = Asset::firstOrCreate([
            'user_id' => $buyOrder->user_id,
            'symbol' => $symbol
        ]);
        $buyerAsset->amount += $amount;
        $buyerAsset->save();

        // Update seller balance
        $seller = $sellOrder->user;
        $seller->balance += ($usdValue - $commission);
        $seller->save();

        // Mark orders as filled
        $buyOrder->status = 2;
        $buyOrder->save();

        $sellOrder->status = 2;
        $sellOrder->save();

        // TODO: Broadcast OrderMatched event here for both users
    }
}
