<?php

namespace App\Http\Controllers\API;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with(['category', 'ticketTypes', 'organizer'])->latest()->get();

        $categoryPopularity = Event::select('category_id')
            ->groupBy('category_id')
            ->selectRaw('COUNT(*) as event_count')
            ->pluck('event_count', 'category_id')
            ->toArray();

        $transformed = $events->map(function ($event) use ($categoryPopularity) {
            return [
                'id' => (string) $event->id,
                'title' => $event->event_name,
                'date' => date('F j, Y', strtotime($event->event_date)),
                'time' => date('g:i A', strtotime($event->start_time)) . ' - ' . date('g:i A', strtotime($event->end_time)),
                'location' => $event->event_location,
                'image' => asset('storage/Event/' . basename($event->image)),
                'organizer' => $event->organizer->name ?? 'Unknown Organizer',
                'description' => $event->event_description,
                'tickets' => $event->ticketTypes->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'name' => $ticket->ticket_name,
                        'price' => (float) $ticket->price,
                        'discount' => (float) $ticket->discount,
                        'quantity_available' => $ticket->quantity_available,
                    ];
                }),
                'category' => $event->category ? $event->category->category_name : 'Uncategorized',
            ];
        });

        return response()->json($transformed);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'event_description' => 'nullable|string',
            'event_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'event_location' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'organizer' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePath = $request->file('image')->store('Event', 'public');

        $user = Auth::user();
        $organizerName = $user->name ?? 'Unknown Organizer';

        $event = Event::create([
            'event_name' => $request->event_name,
            'image' => $imagePath,
            'event_description' => $request->event_description,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'event_location' => $request->event_location,
            'organizer' => $organizerName,
            'user_id' => $user->id,
            'category_id' => $request->category_id ?? null,
        ]);

        $event->image = asset('storage/Event/' . basename($event->image));

        return response()->json($event, 201);
    }

    public function show($id)
    {
        $event = Event::with(['category', 'ticketTypes', 'organizer'])->findOrFail($id);

        $data = [
            'id' => (string) $event->id,
            'title' => $event->event_name,
            'date' => date('F j, Y', strtotime($event->event_date)),
            'time' => date('g:i A', strtotime($event->start_time)) . ' - ' . date('g:i A', strtotime($event->end_time)),
            'location' => $event->event_location,
            'image' => asset('storage/Event/' . basename($event->image)),
            'organizer' => $event->organizer->name ?? 'Unknown Organizer',
            'description' => $event->event_description,
            'tickets' => $event->ticketTypes->map(function ($ticket) {
                return [
                    'name' => $ticket->ticket_name,
                    'price' => (float) $ticket->price,
                    'discount' => (float) $ticket->discount,
                    'quantity_available' => $ticket->quantity_available,
                ];
            }),
            'category' => $event->category ? $event->category->category_name : 'Uncategorized',
        ];

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (Auth::id() !== $event->user_id) {
            return response()->json(['error' => 'Unauthorized. You can only update your own events.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'event_name' => 'sometimes|string|max:255',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
            'event_description' => 'nullable|string',
            'event_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'event_location' => 'sometimes|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'organizer' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'event_name',
            'event_description',
            'event_date',
            'start_time',
            'end_time',
            'event_location',
            'category_id'
        ]);

        // Handle image update if provided
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($event->image && Storage::disk('public')->exists($event->image)) {
                Storage::disk('public')->delete($event->image);
            }

            $imagePath = $request->file('image')->store('Event', 'public');
            $data['image'] = $imagePath;
        }

        // Update organizer name if provided
        if ($request->has('organizer')) {
            $data['organizer'] = $request->organizer;
        }

        $event->update($data);

        // Refresh the event data to include relationships
        $event = Event::with(['category', 'ticketTypes', 'organizer'])->find($id);
        $event->image = asset('storage/Event/' . basename($event->image));

        return response()->json($event);
    }

    // DELETE /api/events/{id}
    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        if ($event->image && Storage::disk('public')->exists($event->image)) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    public function statistics()
    {
        try {
            $totalEvents = Event::count();

            try {
                $totalBookedTickets = Ticket::count();
            } catch (\Exception $e) {
                $totalBookedTickets = Order::where('is_completed', 1)
                    ->orWhere('is_paid', 1)
                    ->count();
            }

            $topEvent = null;

            try {
                $topEvent = Event::withCount(['tickets'])
                    ->orderByDesc('tickets_count')
                    ->first();
            } catch (\Exception $e) {
                try {
                    $topEvent = Event::withCount(['orders'])
                        ->orderByDesc('orders_count')
                        ->first();
                } catch (\Exception $e) {
                    $topEvent = Event::first();
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_events' => $totalEvents,
                    'total_booked_tickets' => $totalBookedTickets,
                    'top_event' => $topEvent ? [
                        'id' => $topEvent->id,
                        'name' => $topEvent->name,
                        'tickets_sold' => $topEvent->tickets_count ?? $topEvent->orders_count ?? 0,
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve event statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
