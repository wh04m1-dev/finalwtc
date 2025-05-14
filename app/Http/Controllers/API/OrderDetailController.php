<?php

namespace App\Http\Controllers\API;

use App\Models\OrderDetail;
use App\Models\TicketType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderDetailController extends Controller
{
    public function index()
    {
        return response()->json(OrderDetail::with(['order', 'ticketType'])->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $ticket = TicketType::findOrFail($request->ticket_type_id);
        $price = $ticket->price - ($ticket->price * ($ticket->discount / 100));

        $orderDetail = OrderDetail::create([
            'order_id' => $request->order_id,
            'ticket_type_id' => $request->ticket_type_id,
            'quantity' => $request->quantity,
            'price_at_purchase' => $price,
            'qr_code' => uniqid('QR-'), // basic placeholder
        ]);

        return response()->json($orderDetail, 201);
    }

    public function show($id)
    {
        return response()->json(OrderDetail::with(['order', 'ticketType'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $orderDetail = OrderDetail::findOrFail($id);

        $request->validate([
            'quantity' => 'integer|min:1',
            'is_scanned' => 'boolean',
        ]);

        $orderDetail->update($request->only('quantity', 'is_scanned'));

        return response()->json($orderDetail);
    }

    public function destroy($id)
    {
        $orderDetail = OrderDetail::findOrFail($id);
        $orderDetail->delete();

        return response()->json(['message' => 'Order detail deleted']);
    }
}
