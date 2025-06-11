<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['ticketType.event'])->get();

        $formattedOrders = $orders->map(function ($order) {
            $event = $order->ticketType->event;

            return [
                'order_id'       => $order->id,
                'order_date'     => $order->order_date ?? $order->created_at->format('Y-m-d H:i:s'),
                'order_status'   => $order->order_status,
                'paymentStatus'  => $order->payment_status,
                'quantity'       => $order->quantity,
                'unitPrice'      => number_format($order->price_at_purchase, 2),
                'total'          => number_format($order->total_amount, 2),
                'ticketType'     => $order->ticketType->ticket_name ?? null,
                'eventTitle'     => $event->event_name ?? null,
                'eventDate'      => $event ? date('F j, Y', strtotime($event->event_date)) : null,
                'eventTime'      => $event ? $event->start_time . ' - ' . $event->end_time : null,
                'eventLocation'  => $event->event_location ?? null,
                'eventImage'     => $event ? url('storage/' . $event->image) : null,
                'qr_code'        => $order->qr_code,
                'is_scanned'     => (bool) $order->is_scanned,
            ];
        });

        return response()->json($formattedOrders, 200);
    }

    // Store a new order
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required|exists:users,id',
            'ticket_type_id'    => 'required|exists:ticket_types,id',
            'order_status'      => 'in:Cart,Confirmed,Paid,Cancelled',
            'payment_status'    => 'in:Pending,Paid,Failed',
            'quantity'          => 'required|integer|min:1',
            'price_at_purchase' => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'purchased_at'      => 'nullable|date',
            'qr_code'           => 'nullable|string',
            'is_scanned'        => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::create($request->all());

        return response()->json($order, 201);
    }

    // Show a single order
    public function show($id)
    {
        $order = Order::with(['user', 'ticketType'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order, 200);
    }

    // Update an order
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_status'      => 'in:Cart,Confirmed,Paid,Cancelled',
            'payment_status'    => 'in:Pending,Paid,Failed',
            'quantity'          => 'integer|min:1',
            'price_at_purchase' => 'numeric|min:0',
            'total_amount'      => 'numeric|min:0',
            'purchased_at'      => 'nullable|date',
            'qr_code'           => 'nullable|string',
            'is_scanned'        => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($request->all());

        return response()->json($order, 200);
    }

    // Delete an order
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully'], 200);
    }

    // Optional: Get orders by user
    public function getByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->with('ticketType')->get();
        return response()->json($orders, 200);
    }
}
