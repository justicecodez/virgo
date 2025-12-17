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
