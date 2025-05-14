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
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'ticket_name' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'quantity_available' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $ticketType = TicketType::create($request->all());

        return response()->json($ticketType, 201);
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
