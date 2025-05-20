<?php

namespace App\Http\Controllers\API;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    // GET /api/events
    public function index()
    {
        $events = Event::with(['category', 'ticketTypes', 'organizer'])->latest()->get();

        // Calculate category popularity
        $categoryPopularity = Event::select('category_id')
            ->groupBy('category_id')
            ->selectRaw('COUNT(*) as event_count')
            ->pluck('event_count', 'category_id')
            ->toArray();

        $transformed = $events->map(function ($event) use ($categoryPopularity) {
            // Calculate event duration in hours
            $startTime = strtotime($event->start_time);
            $endTime = strtotime($event->end_time);
            $durationHours = ($endTime - $startTime) / 3600;

            // Get total ticket quantity available
            $totalTickets = $event->ticketTypes->sum('quantity_available');

            // Calculate Fun Index: duration * 10 + category popularity * 5 + tickets * 2 + base score
            $categoryScore = $categoryPopularity[$event->category_id ?? 0] ?? 0;
            //$funIndex = ($durationHours * 10) + ($categoryScore * 5) + ($totalTickets * 2) + 10; // Added base score of 10

            // Add description-based score (e.g., +5 if "fun" or "party" appears)
            $descriptionScore = (stripos($event->event_description, 'fun') !== false || stripos($event->event_description, 'party') !== false) ? 5 : 0;

            return [
                'id' => (string) $event->id,
                'title' => $event->event_name,
                'date' => date('F j, Y', strtotime($event->event_date)),
                'time' => date('g:i A', strtotime($event->start_time)) . ' - ' . date('g:i A', strtotime($event->end_time)),
                'location' => $event->event_location,
                'image' => asset('storage/' . $event->image),
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
                //'fun_index' => round($funIndex + $descriptionScore, 2),
            ];
        });

        return response()->json($transformed);
    }

    // POST /api/events
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

        $imagePath = $request->file('image')->store('events', 'public');

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

        $event->image = asset('storage/' . $event->image);

        return response()->json($event, 201);
    }

    // GET /api/events/{id}
    public function show($id)
    {
        $event = Event::with(['category', 'ticketTypes', 'organizer'])->findOrFail($id);

        $data = [
            'id' => (string) $event->id,
            'title' => $event->event_name,
            'date' => date('F j, Y', strtotime($event->event_date)),
            'time' => date('g:i A', strtotime($event->start_time)) . ' - ' . date('g:i A', strtotime($event->end_time)),
            'location' => $event->event_location,
            'image' => asset('storage/' . $event->image),
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
    // PUT /api/events/{id}
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
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

        if ($request->hasFile('image')) {
            if ($event->image && Storage::disk('public')->exists($event->image)) {
                Storage::disk('public')->delete($event->image);
            }

            $event->image = $request->file('image')->store('events', 'public');
        }

        $event->update([
            'event_name' => $request->event_name,
            'event_description' => $request->event_description,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'event_location' => $request->event_location,
            'category_id' => $request->category_id,
            'organizer' => $request->organizer,
            'image' => $event->image,
        ]);

        $event->image = Storage::url($event->image);

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
}
