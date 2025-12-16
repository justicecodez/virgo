<?php

namespace App\Services;

use App\Events\Event\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
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

    public function place(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {

            // 1. Create the order
            $order = $data['side'] === 'buy'
                ? $this->placeBuy($data, $user)
                : $this->placeSell($data, $user);

            // 2. Try to match it
            $this->attemptMatch($order);

            // 3. Return it
            return $order;
        });
    }

    // public function createOrder(User $user, string $symbol, string $side, float $price, float $amount)
    // {
    //     try {
    //         return DB::transaction(function () use ($user, $symbol, $side, $price, $amount) {

    //             if ($side === 'buy') {
    //                 return $this->handleBuy($user, $symbol, $price, $amount);
    //             } else {
    //                 return $this->handleSell($user, $symbol, $price, $amount);
    //             }
    //         }, 3); // 3 = number of times to retry if deadlock occurs

    //     } catch (Exception $e) {
    //         // Log error for debugging
    //         Log::error('Order creation failed: ' . $e->getMessage(), [
    //             'user_id' => $user->id,
    //             'symbol' => $symbol,
    //             'side' => $side,
    //             'price' => $price,
    //             'amount' => $amount
    //         ]);

    //         // Throw clean exception to controller
    //         throw new Exception($e->getMessage());
    //     }
    // }

    // protected function handleBuy(User $user, string $symbol, float $price, float $amount)
    // {
    //     $totalCost = bcmul($price, $amount, 8); // precise decimal multiplication

    //     // Lock user row to prevent race conditions
    //     $user = User::where('id', $user->id)->lockForUpdate()->first();

    //     if ($user->balance < $totalCost) {
    //         throw new Exception('Insufficient USD balance');
    //     }

    //     // Deduct balance
    //     $user->balance -= $totalCost;
    //     $user->save();

    //     // Create order
    //     return Order::create([
    //         'user_id' => $user->id,
    //         'symbol' => $symbol,
    //         'side' => 'buy',
    //         'price' => $price,
    //         'amount' => $amount,
    //         'status' => 1, // open
    //     ]);
    // }

    // protected function handleSell(User $user, string $symbol, float $price, float $amount)
    // {
    //     // Lock asset row
    //     $asset = Asset::where('user_id', $user->id)
    //         ->where('symbol', $symbol)
    //         ->lockForUpdate()
    //         ->first();

    //     if (!$asset || $asset->amount < $amount) {
    //         throw new Exception('Insufficient asset balance');
    //     }

    //     // Move amount to locked
    //     $asset->amount -= $amount;
    //     $asset->locked_amount += $amount;
    //     $asset->save();

    //     // Create order
    //     return Order::create([
    //         'user_id' => $user->id,
    //         'symbol' => $symbol,
    //         'side' => 'sell',
    //         'price' => $price,
    //         'amount' => $amount,
    //         'status' => 1, // open
    //     ]);
    // }

    // public function matchOrder(Order $order)
    // {
    //     try {
    //         return DB::transaction(function () use ($order) {

    //             if ($order->side === 'buy') {
    //                 // Find first SELL order that matches
    //                 $sellOrder = Order::where('symbol', $order->symbol)
    //                     ->where('side', 'sell')
    //                     ->where('status', 1)
    //                     ->where('price', '<=', $order->price)
    //                     ->orderBy('created_at')
    //                     ->lockForUpdate()
    //                     ->first();

    //                 if (!$sellOrder) {
    //                     return null; // No match
    //                 }

    //                 $this->executeTrade($order, $sellOrder);
    //             } else {
    //                 // SELL side
    //                 $buyOrder = Order::where('symbol', $order->symbol)
    //                     ->where('side', 'buy')
    //                     ->where('status', 1)
    //                     ->where('price', '>=', $order->price)
    //                     ->orderBy('created_at')
    //                     ->lockForUpdate()
    //                     ->first();

    //                 if (!$buyOrder) {
    //                     return null; // No match
    //                 }

    //                 $this->executeTrade($buyOrder, $order);
    //             }
    //         }, 3); // retry 3 times if deadlock
    //     } catch (\Exception $e) {
    //         Log::error('Matching failed: ' . $e->getMessage(), [
    //             'order_id' => $order->id,
    //             'symbol' => $order->symbol,
    //         ]);
    //         throw new \Exception($e->getMessage());
    //     }
    // }

    protected function executeTrade(Order $buyOrder, Order $sellOrder): void
    {
        DB::transaction(function () use ($buyOrder, $sellOrder) {

            $symbol = $buyOrder->symbol;
            $amount = $sellOrder->amount;
            $price = $sellOrder->price;

            $usdValue = bcmul($amount, $price, 8);
            $commission = bcmul($usdValue, '0.015', 8);

            $buyer = User::where('id', $buyOrder->user_id)->lockForUpdate()->first();
            $seller = User::where('id', $sellOrder->user_id)->lockForUpdate()->first();

            $sellerAsset = Asset::where('user_id', $seller->id)
                ->where('symbol', $symbol)
                ->lockForUpdate()
                ->first();

            $buyerAsset = Asset::firstOrCreate(
                ['user_id' => $buyer->id, 'symbol' => $symbol],
                ['amount' => 0, 'locked_amount' => 0]
            );

            // Buyer gets asset
            $buyerAsset->amount = bcadd($buyerAsset->amount, $amount, 8);
            $buyerAsset->save();

            // Seller gets USD minus commission
            $seller->balance = bcadd(
                $seller->balance,
                bcsub($usdValue, $commission, 8),
                8
            );
            $seller->save();

            // Unlock seller asset
            $sellerAsset->locked_amount = bcsub(
                $sellerAsset->locked_amount,
                $amount,
                8
            );
            $sellerAsset->save();

            $buyOrder->status = 2;
            $sellOrder->status = 2;
            $buyOrder->save();
            $sellOrder->save();

            event(new OrderMatched($buyOrder, $sellOrder));
        });
    }

    protected function placeBuy(array $data, User $user): Order
    {
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        $cost = bcmul($data['price'], $data['amount'], 8);

        if (bccomp($user->balance, $cost, 8) < 0) {
            throw new Exception('Insufficient balance');
        }

        $user->balance = bcsub($user->balance, $cost, 8);
        $user->save();

        return Order::create([
            'user_id' => $user->id,
            'symbol' => $data['symbol'],
            'side' => 'buy',
            'price' => $data['price'],
            'amount' => $data['amount'],
            'status' => 1,
        ]);
    }

    protected function placeSell(array $data, User $user): Order
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $data['symbol'])
            ->lockForUpdate()
            ->first();

        if (! $asset) {
            throw new Exception('You do not own this asset');
        }

        if (bccomp($asset->amount, $data['amount'], 8) < 0) {
            throw new Exception('Insufficient asset balance');
        }

        $asset->amount = bcsub($asset->amount, $data['amount'], 8);
        $asset->locked_amount = bcadd($asset->locked_amount, $data['amount'], 8);
        $asset->save();

        return Order::create([
            'user_id' => $user->id,
            'symbol' => $data['symbol'],
            'side' => 'sell',
            'price' => $data['price'],
            'amount' => $data['amount'],
            'status' => 1,
        ]);
    }

    protected function attemptMatch(Order $order): void
    {
        if ($order->side === 'buy') {
            $counter = Order::where('symbol', $order->symbol)
                ->where('side', 'sell')
                ->where('status', 1)
                ->where('price', '<=', $order->price)
                ->orderBy('price')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        } else {
            $counter = Order::where('symbol', $order->symbol)
                ->where('side', 'buy')
                ->where('status', 1)
                ->where('price', '>=', $order->price)
                ->orderByDesc('price')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        if (! $counter) {
            return;
        }

        if ($order->side === 'buy') {
            $this->executeTrade($order, $counter);
        } else {
            $this->executeTrade($counter, $order);
        }
    }
}
