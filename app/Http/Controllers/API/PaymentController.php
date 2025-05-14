<?php

namespace App\Http\Controllers\API;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    // Create a new payment
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:Credit Card,PayPal,Bank Transfer',
            'payment_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:Pending,Successful,Failed',
        ]);

        // Check if the order exists and if it's not already paid
        $order = Order::findOrFail($request->order_id);

        if ($order->payment_status == 'Paid') {
            throw ValidationException::withMessages(['order_id' => ['This order is already paid.']]);
        }

        // Create the payment
        $payment = Payment::create([
            'order_id' => $request->order_id,
            'payment_method' => $request->payment_method,
            'payment_amount' => $request->payment_amount,
            'payment_status' => $request->payment_status,
        ]);

        // Update the order status
        if ($payment->payment_status == 'Successful') {
            $order->update(['payment_status' => 'Paid']);
        }

        return response()->json($payment, 201);
    }

    // Get all payments
    public function index()
    {
        return response()->json(Payment::with('order')->get());
    }

    // Get payment details by ID
    public function show($id)
    {
        return response()->json(Payment::with('order')->findOrFail($id));
    }

    // Update payment details (e.g., status)
    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'payment_status' => 'required|in:Pending,Successful,Failed',
        ]);

        $payment->update([
            'payment_status' => $request->payment_status,
        ]);

        return response()->json($payment);
    }

    // Delete a payment
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
