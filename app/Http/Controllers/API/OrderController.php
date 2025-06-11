<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TicketType;
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

        // Check ticket availability
        $ticketType = TicketType::findOrFail($request->ticket_type_id);

        if ($ticketType->quantity_available < $request->quantity) {
            return response()->json(['message' => 'Not enough tickets available'], 400);
        }

        // Create the order
        $order = Order::create($request->all());

        // Update the ticket quantity
        $ticketType->quantity_available -= $request->quantity;
        $ticketType->save();

        return response()->json($order, 201);
    }

    public function show($id)
    {
        $order = Order::with(['user', 'ticketType'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order, 200);
    }

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

        // Handle quantity changes
        if ($request->has('quantity') && $request->quantity != $order->quantity) {
            $ticketType = TicketType::findOrFail($order->ticket_type_id);

            // Calculate the difference
            $quantityDifference = $order->quantity - $request->quantity;

            // Check if we have enough tickets if increasing the order quantity
            if ($quantityDifference < 0 && $ticketType->quantity_available < abs($quantityDifference)) {
                return response()->json(['message' => 'Not enough tickets available'], 400);
            }

            // Update the ticket quantity
            $ticketType->quantity_available += $quantityDifference;
            $ticketType->save();
        }

        $order->update($request->all());

        return response()->json($order, 200);
    }

    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Return the tickets to available quantity
        $ticketType = TicketType::findOrFail($order->ticket_type_id);
        $ticketType->quantity_available += $order->quantity;
        $ticketType->save();

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully'], 200);
    }

    public function getByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->with('ticketType')->get();
        return response()->json($orders, 200);
    }
}
