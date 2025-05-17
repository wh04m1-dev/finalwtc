<?php

namespace App\Http\Controllers\API;

use App\Models\TicketType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TicketTypeController extends Controller
{
    public function index()
    {
        return response()->json(TicketType::with('event')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tickets'                       => 'required|array|min:1',
            'tickets.*.event_id'            => 'required|exists:events,id',
            'tickets.*.ticket_name'         => 'required|string|max:50',
            'tickets.*.price'               => 'required|numeric|min:0',
            'tickets.*.quantity_available'  => 'required|integer|min:1',
            'tickets.*.discount'            => 'nullable|numeric|min:0|max:100',
        ]);

        $createdTickets = [];

        foreach ($validated['tickets'] as $ticketData) {
            $createdTickets[] = TicketType::create($ticketData);
        }

        return response()->json($createdTickets, 201);
    }

    public function show($id)
    {
        return response()->json(TicketType::with('event')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $ticketType = TicketType::findOrFail($id);

        $request->validate([
            'event_id' => 'required|exists:events,id',
            'ticket_name' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'quantity_available' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $ticketType->update($request->all());

        return response()->json($ticketType);
    }

    public function destroy($id)
    {
        $ticketType = TicketType::findOrFail($id);
        $ticketType->delete();

        return response()->json(['message' => 'Ticket type deleted successfully']);
    }
}
