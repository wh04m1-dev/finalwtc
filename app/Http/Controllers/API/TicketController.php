<?php

namespace App\Http\Controllers\API;

use App\Models\Ticket;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // Generate a ticket after order completion
    public function generateTicket(Request $request, $orderDetailId)
    {
        $orderDetail = OrderDetail::findOrFail($orderDetailId);

        // Create a unique ticket code (this can be used for QR generation)
        $ticketCode = Str::random(10); // You can customize this logic

        // Create the ticket
        $ticket = Ticket::create([
            'order_detail_id' => $orderDetail->id,
            'ticket_code' => $ticketCode,
            'ticket_type_id' => $orderDetail->ticketType->id,
            'user_id' => $orderDetail->order->user_id,
            'status' => 'active',
        ]);

        return response()->json($ticket, 201);
    }

    // Mark ticket as scanned/used when it's scanned at the event
    public function scanTicket($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        // Check if the ticket is still active
        if (!$ticket->isActive()) {
            return response()->json(['message' => 'Ticket is already used or expired.'], 400);
        }

        // Mark the ticket as used
        $ticket->markAsUsed();

        return response()->json(['message' => 'Ticket successfully scanned.']);
    }
}
