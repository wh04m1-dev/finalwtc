<?php

namespace App\Http\Controllers\API;

use App\Models\Discount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DiscountController extends Controller
{
    public function index()
    {
        return response()->json(Discount::with('event')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'discount_code' => 'required|string|max:50|unique:discounts',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $discount = Discount::create($request->all());

        return response()->json($discount, 201);
    }

    public function show($id)
    {
        return response()->json(Discount::with('event')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);

        $request->validate([
            'discount_code' => 'string|max:50|unique:discounts,discount_code,' . $id,
            'discount_percentage' => 'numeric|min:0|max:100',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        $discount->update($request->all());

        return response()->json($discount);
    }

    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return response()->json(['message' => 'Discount deleted successfully']);
    }

    public function validateDiscount(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'discount_code' => 'required|string'
        ]);

        $discount = Discount::where('event_id', $request->event_id)
            ->where('discount_code', $request->discount_code)
            ->first();

        if (!$discount || !$discount->isActive()) {
            return response()->json(['message' => 'Invalid or expired discount.'], 400);
        }

        return response()->json([
            'message' => 'Discount valid',
            'discount' => $discount
        ]);
    }

}
