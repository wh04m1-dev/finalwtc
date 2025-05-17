<?php

namespace App\Http\Controllers\API;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    // GET /api/events
    public function index()
    {
        $events = Event::with('category')->latest()->get();

        // Add full URL for image
        $events->transform(function ($event) {
            $event->image = asset('storage/' . $event->image);
            return $event;
        });

        return response()->json($events);
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle image upload
        $imagePath = $request->file('image')->store('events', 'public');

        // Save raw path to DB
        $event = Event::create([
            'event_name' => $request->event_name,
            'image' => $imagePath,
            'event_description' => $request->event_description,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'event_location' => $request->event_location,
            'category_id' => $request->category_id,
        ]);

        // Only modify image path in the response
        $event->image = asset('storage/' . $event->image);

        return response()->json($event, 201);
    }

    // GET /api/events/{id}
    public function show($id)
    {
        $event = Event::with('category')->findOrFail($id);
        $event->image = asset('storage/' . $event->image);

        return response()->json($event);
    }

    // PUT/PATCH /api/events/{id}
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle optional image update
        if ($request->hasFile('image')) {
            // Delete old image if exists
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
            'image' => $event->image,
        ]);

        // Add full image URL
        $event->image = Storage::url($event->image);

        return response()->json($event);
    }

    // DELETE /api/events/{id}
    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        // Delete image file
        if ($event->image && Storage::disk('public')->exists($event->image)) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
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
