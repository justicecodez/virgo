<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PlaceOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(string $symbol)
    {

        $symbol = strtoupper($symbol);

        if (!in_array($symbol, ['BTC', 'ETH'])) {
            return response()->json([
                'message' => 'Invalid symbol'
            ], 422);
        }

        $data = Order::where('symbol', $symbol)
            ->where('status', 1)
            ->orderBy('price')
            ->get();
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function myOrders(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => Order::where('user_id', $request->user()->id)
                ->orderByDesc('id')
                ->get()
        ]);
    }

    public function store(PlaceOrderRequest $request, OrderService $service)
    {
        try {
            $order = $service->place(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'status' => true,
                'order' => $order,
            ]);
        } catch (Exception $e) {
            Log::error('Error storing Order :' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
