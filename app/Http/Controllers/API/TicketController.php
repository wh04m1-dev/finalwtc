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


// {
//     id: "1",
//     title: "Summer Music Festival",
//     date: "June 15, 2025",
//     time: "4:00 PM - 11:00 PM",
//     location: "Central Park, New York",
//     image: "/event1.jpg",
//     organizer: "Music Events Inc.",
//     description:
//       "Join us for the biggest summer music festival featuring top artists from around the world. Enjoy a day of amazing performances, food, and fun activities for all ages.",
//     tickets: [
//       {
//         type: "vip",
//         name: "VIP2",
//         price: 129.99,
//         description: "Front row access, exclusive lounge, complimentary drinks",
//         discount: {
//           percentage: 13,
//           originalPrice: 149.99,
//         },
//       },
//       {
//         type: "premium",
//         name: "Premium",
//         price: 79.99,
//         description: "Priority seating, fast-track entry",
//         discount: {
//           percentage: 20,
//           originalPrice: 99.99,
//         },
//       },
//       { type: "standard", name: "Standard", price: 49.99, description: "General admission" },
//     ],
//     category: "Festival",
//   },
