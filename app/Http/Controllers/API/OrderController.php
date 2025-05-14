<?php

namespace App\Http\Controllers\API;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index()
    {
        return response()->json(Order::with('user')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'total_amount' => 'required|numeric|min:0',
            'order_status' => 'in:Cart,Confirmed,Paid,Cancelled',
            'payment_status' => 'in:Pending,Paid,Failed',
        ]);

        $order = Order::create([
            'user_id' => $request->user_id,
            'order_status' => $request->order_status ?? 'Cart',
            'total_amount' => $request->total_amount,
            'payment_status' => $request->payment_status ?? 'Pending',
            'purchased_at' => $request->payment_status === 'Paid' ? now() : null,
        ]);

        return response()->json($order, 201);
    }

    public function show($id)
    {
        $order = Order::with('user')->findOrFail($id);
        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'order_status' => 'in:Cart,Confirmed,Paid,Cancelled',
            'payment_status' => 'in:Pending,Paid,Failed',
            'total_amount' => 'numeric|min:0',
        ]);

        $order->update([
            'order_status' => $request->order_status ?? $order->order_status,
            'payment_status' => $request->payment_status ?? $order->payment_status,
            'total_amount' => $request->total_amount ?? $order->total_amount,
            'purchased_at' => $request->payment_status === 'Paid' ? now() : $order->purchased_at,
        ]);

        return response()->json($order);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
