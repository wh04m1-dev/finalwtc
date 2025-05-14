<?php

namespace App\Http\Controllers\API;

use App\Models\Event;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(Event::with('category')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_name' => 'required|string|max:255',
            'event_description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_location' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $event = Event::create($request->all());

        return response()->json($event, 201);
    }

    public function show($id)
    {
        $event = Event::with('category')->findOrFail($id);
        return response()->json($event);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'event_name' => 'required|string|max:255',
            'event_description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_location' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $event->update($request->all());

        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }
}
