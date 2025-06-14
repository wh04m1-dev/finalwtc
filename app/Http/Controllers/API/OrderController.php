<?php

/*

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::with(['ticketType.event'])->get();

            $formattedOrders = $orders->map(function ($order) {
                $event = $order->ticketType->event ?? null;
                $eventImageUrl = null;

                if ($event && $event->image) {
                    $eventImageUrl = $this->getFullImageUrl($event->image);
                }

                return [
                    'order_id'       => $order->id,
                    'order_date'     => $order->order_date ?? $order->created_at->format('Y-m-d H:i:s'),
                    'order_status'   => $order->order_status,
                    'paymentStatus'  => $order->payment_status,
                    'quantity'       => $order->quantity,
                    'unitPrice'      => number_format($order->price_at_purchase, 2),
                    'total'          => number_format($order->total_amount, 2),
                    'ticketType'     => $order->ticketType->ticket_name ?? null,
                    'eventTitle'     => $event->event_name ?? null,
                    'eventDate'      => $event ? date('F j, Y', strtotime($event->event_date)) : null,
                    'eventTime'      => $event ? $event->start_time . ' - ' . $event->end_time : null,
                    'eventLocation'  => $event->event_location ?? null,
                    'eventImage'     => $eventImageUrl,
                    'qr_code'        => $order->qr_code ? $this->getFullImageUrl($order->qr_code) : null,
                    'is_scanned'     => (bool) $order->is_scanned,
                    'order_image'    => $order->image ? $this->getOrderImageUrl($order->image) : null,
                ];
            });

            return response()->json($formattedOrders, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $qrData = json_decode($request->qr_code, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($qrData) || !isset($qrData['order_id'])) {
                return response()->json(['errors' => ['qr_code' => ['The selected qr code is invalid.']]], 422);
            }

            $order = Order::where('id', $qrData['order_id'])->first();

            if (!$order) {
                return response()->json(['errors' => ['qr_code' => ['The selected qr code is invalid.']]], 422);
            }

            if ($order->is_scanned) {
                return response()->json([
                    'message' => 'Ticket has already been scanned',
                ], 400);
            }

            $order->update(['is_scanned' => true]);

            return response()->json([
                'message' => 'Ticket successfully scanned',
                'order_id' => $order->id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to scan ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required|exists:users,id',
            'ticket_type_id'    => 'required|exists:ticket_types,id',
            'order_status'      => 'required|in:Cart,Confirmed,Paid,Cancelled',
            'payment_status'    => 'required|in:Pending,Paid,Failed',
            'quantity'          => 'required|integer|min:1',
            'price_at_purchase' => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'purchased_at'      => 'nullable|date',
            'is_scanned'        => 'boolean',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ticketType = TicketType::with('event')->findOrFail($request->ticket_type_id);

            if ($ticketType->quantity_available < $request->quantity) {
                return response()->json([
                    'message' => 'Not enough tickets available',
                    'available' => $ticketType->quantity_available
                ], 400);
            }

            $orderData = $request->except('image');
            if (empty($orderData['order_status'])) {
                $orderData['order_status'] = 'Cart';
            }
            if (empty($orderData['payment_status'])) {
                $orderData['payment_status'] = 'Pending';
            }

            $qrData = json_encode([
                'order_id' => Str::random(10),
                'ticket_type_id' => $request->ticket_type_id,
                'event_id' => $ticketType->event->id,
                'quantity' => $request->quantity,
                'order_number' => 'ORD-' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT),
            ]);
            $qrCodeFilename = 'qr_codes/' . Str::random(20) . '.png';
            QrCode::format('png')->size(300)->generate($qrData, storage_path('app/public/' . $qrCodeFilename));
            $orderData['qr_code'] = $qrCodeFilename;

            $order = Order::create($orderData);

            $qrData = json_encode([
                'order_id' => $order->id,
                'ticket_type_id' => $request->ticket_type_id,
                'event_id' => $ticketType->event->id,
                'quantity' => $request->quantity,
                'order_number' => 'ORD-' . str_pad($order->id, 8, '0', STR_PAD_LEFT),
            ]);
            QrCode::format('png')->size(300)->generate($qrData, storage_path('app/public/' . $qrCodeFilename));
            $order->update(['qr_code' => $qrCodeFilename]);

            if ($request->hasFile('image')) {
                $order->image = $this->storeOrderImage($request->file('image'));
                $order->save();
            }

            $ticketType->decrement('quantity_available', $request->quantity);

            $event = $ticketType->event;
            $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
                'order_image_url' => $order->image ? $this->getOrderImageUrl($order->image) : null,
                'qr_code_url' => $this->getFullImageUrl($qrCodeFilename),
                'event_details' => $event ? [
                    'title' => $event->event_name,
                    'date' => $event->event_date,
                    'image' => $eventImageUrl,
                ] : null
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::with(['user', 'ticketType.event'])->find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $event = $order->ticketType->event ?? null;
            $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

            $formattedOrder = [
                'id' => $order->id,
                'user' => [
                    'id' => $order->user->id ?? null,
                    'name' => $order->user->name ?? null,
                    'email' => $order->user->email ?? null,
                ],
                'order_date' => $order->order_date ?? $order->created_at->format('Y-m-d H:i:s'),
                'status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'quantity' => $order->quantity,
                'price' => number_format($order->price_at_purchase, 2),
                'total' => number_format($order->total_amount, 2),
                'ticket_type' => [
                    'id' => $order->ticketType->id ?? null,
                    'name' => $order->ticketType->ticket_name ?? null,
                    'description' => $order->ticketType->description ?? null,
                ],
                'event' => [
                    'id' => $event->id ?? null,
                    'title' => $event->event_name ?? null,
                    'date' => $event ? date('F j, Y', strtotime($event->event_date)) : null,
                    'time' => $event ? $event->start_time . ' - ' . $event->end_time : null,
                    'location' => $event->event_location ?? null,
                    'image' => $eventImageUrl,
                    'description' => $event->description ?? null,
                ],
                'qr_code' => $order->qr_code ? $this->getFullImageUrl($order->qr_code) : null,
                'is_scanned' => (bool) $order->is_scanned,
                'order_image' => $order->image ? $this->getOrderImageUrl($order->image) : null,
            ];

            return response()->json($formattedOrder, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $order = Order::with(['ticketType.event'])->find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'order_status'      => 'in:Cart,Confirmed,Paid,Cancelled',
                'payment_status'    => 'in:Pending,Paid,Failed',
                'quantity'          => 'integer|min:1',
                'price_at_purchase' => 'numeric|min:0',
                'total_amount'      => 'numeric|min:0',
                'purchased_at'      => 'nullable|date',
                'is_scanned'        => 'boolean',
                'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->has('quantity') && $request->quantity != $order->quantity) {
                $ticketType = TicketType::findOrFail($order->ticket_type_id);
                $quantityDifference = $order->quantity - $request->quantity;

                if ($quantityDifference < 0 && $ticketType->quantity_available < abs($quantityDifference)) {
                    return response()->json([
                        'message' => 'Not enough tickets available',
                        'available' => $ticketType->quantity_available
                    ], 400);
                }

                $ticketType->increment('quantity_available', $quantityDifference);
            }

            if ($request->hasFile('image')) {
                if ($order->image) {
                    Storage::delete('public/Order/' . $order->image);
                }
                $order->image = $this->storeOrderImage($request->file('image'));
            }

            $order->update($request->except('image'));

            $updatedOrder = Order::with(['ticketType.event'])->find($id);
            $event = $updatedOrder->ticketType->event ?? null;
            $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $updatedOrder,
                'order_image_url' => $updatedOrder->image ? $this->getOrderImageUrl($updatedOrder->image) : null,
                'qr_code_url' => $updatedOrder->qr_code ? $this->getFullImageUrl($updatedOrder->qr_code) : null,
                'event_image' => $eventImageUrl
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($order->image) {
                Storage::delete('public/Order/' . $order->image);
            }
            if ($order->qr_code) {
                Storage::delete('public/' . $order->qr_code);
            }

            $ticketType = TicketType::findOrFail($order->ticket_type_id);
            $ticketType->increment('quantity_available', $order->quantity);

            $order->delete();

            return response()->json(['message' => 'Order deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByUser($userId)
    {
        try {
            $orders = Order::where('user_id', $userId)
                ->with(['ticketType.event'])
                ->get();

            $formattedOrders = $orders->map(function ($order) {
                $event = $order->ticketType->event ?? null;
                $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

                return [
                    'id' => $order->id,
                    'order_date' => $order->order_date ?? $order->created_at->format('Y-m-d H:i:s'),
                    'status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                    'quantity' => $order->quantity,
                    'total' => number_format($order->total_amount, 2),
                    'ticket_type' => [
                        'id' => $order->ticketType->id ?? null,
                        'name' => $order->ticketType->ticket_name ?? null,
                    ],
                    'event' => [
                        'id' => $event->id ?? null,
                        'title' => $event->event_name ?? null,
                        'date' => $event ? date('F j, Y', strtotime($event->event_date)) : null,
                        'image' => $eventImageUrl,
                    ],
                    'qr_code' => $order->qr_code ? $this->getFullImageUrl($order->qr_code) : null,
                    'order_image' => $order->image ? $this->getOrderImageUrl($order->image) : null,
                ];
            });

            return response()->json($formattedOrders, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getFullImageUrl($path)
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = str_replace('storage/', '', $path);
        return asset('storage/' . $path);
    }

    protected function getOrderImageUrl($filename)
    {
        return asset('storage/Order/' . $filename);
    }

    protected function storeOrderImage($file)
    {
        $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/Order', $filename);
        return $filename;
    }
}
*/

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::with(['ticketType.event'])->get();

            $formattedOrders = $orders->map(function ($order) {
                $event = $order->ticketType->event ?? null;
                $eventImageUrl = null;

                if ($event && $event->image) {
                    $eventImageUrl = $this->getFullImageUrl($event->image);
                }

                return [
                    'order_id'       => $order->id,
                    'order_date'     => $order->order_date ?? $order->created_at->format('Y-m-d H:i:s'),
                    'order_status'   => $order->order_status,
                    'paymentStatus'  => $order->payment_status,
                    'quantity'       => $order->quantity,
                    'unitPrice'      => number_format($order->price_at_purchase, 2),
                    'total'          => number_format($order->total_amount, 2),
                    'ticketType'     => $order->ticketType->ticket_name ?? null,
                    'eventTitle'     => $event->event_name ?? null,
                    'eventDate'      => $event ? date('F j, Y', strtotime($event->event_date)) : null,
                    'eventTime'      => $event ? $event->start_time . ' - ' . $event->end_time : null,
                    'eventLocation'  => $event->event_location ?? null,
                    'eventImage'     => $eventImageUrl,
                    'is_scanned'     => (bool) $order->is_scanned,
                    'order_image'    => $order->image ? $this->getOrderImageUrl($order->image) : null,
                ];
            });

            return response()->json($formattedOrders, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $order = Order::find($request->order_id);

            if (!$order) {
                return response()->json(['errors' => ['order_id' => ['The selected order is invalid.']]], 422);
            }

            if ($order->is_scanned) {
                return response()->json([
                    'message' => 'Ticket has already been scanned',
                ], 400);
            }

            $order->update(['is_scanned' => true]);

            return response()->json([
                'message' => 'Ticket successfully scanned',
                'order_id' => $order->id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to scan ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required|exists:users,id',
            'ticket_type_id'    => 'required|exists:ticket_types,id',
            'order_status'      => 'required|in:Cart,Confirmed,Paid,Cancelled',
            'payment_status'    => 'required|in:Pending,Paid,Failed',
            'quantity'          => 'required|integer|min:1',
            'price_at_purchase' => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'purchased_at'      => 'nullable|date',
            'is_scanned'        => 'boolean',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ticketType = TicketType::with('event')->findOrFail($request->ticket_type_id);

            if ($ticketType->quantity_available < $request->quantity) {
                return response()->json([
                    'message' => 'Not enough tickets available',
                    'available' => $ticketType->quantity_available
                ], 400);
            }

            $orderData = $request->except('image');
            if (empty($orderData['order_status'])) {
                $orderData['order_status'] = 'Cart';
            }
            if (empty($orderData['payment_status'])) {
                $orderData['payment_status'] = 'Pending';
            }

            $order = Order::create($orderData);

            // Handle file upload
            if ($request->hasFile('image')) {
                $order->image = $this->storeOrderImage($request->file('image'));
                $order->save();
            }

            $ticketType->decrement('quantity_available', $request->quantity);

            $event = $ticketType->event;
            $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
                'order_image_url' => $order->image ? $this->getOrderImageUrl($order->image) : null,
                'event_details' => $event ? [
                    'title' => $event->event_name,
                    'date' => $event->event_date,
                    'image' => $eventImageUrl,
                ] : null
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::with(['user', 'ticketType.event'])->find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $event = $order->ticketType->event ?? null;
            $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

            $formattedOrder = [
                'id' => $order->id,
                'user' => [
                    'id' => $order->user->id ?? null,
                    'name' => $order->user->name ?? null,
                    'email' => $order->user->email ?? null,
                ],
                'order_date' => $order->order_date ?? $order->created_at->format('Y-m-d H:i:s'),
                'status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'quantity' => $order->quantity,
                'price' => number_format($order->price_at_purchase, 2),
                'total' => number_format($order->total_amount, 2),
                'ticket_type' => [
                    'id' => $order->ticketType->id ?? null,
                    'name' => $order->ticketType->ticket_name ?? null,
                    'description' => $order->ticketType->description ?? null,
                ],
                'event' => [
                    'id' => $event->id ?? null,
                    'title' => $event->event_name ?? null,
                    'date' => $event ? date('F j, Y', strtotime($event->event_date)) : null,
                    'time' => $event ? $event->start_time . ' - ' . $event->end_time : null,
                    'location' => $event->event_location ?? null,
                    'image' => $eventImageUrl,
                    'description' => $event->description ?? null,
                ],
                'is_scanned' => (bool) $order->is_scanned,
                'order_image' => $order->image ? $this->getOrderImageUrl($order->image) : null,
            ];

            return response()->json($formattedOrder, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $order = Order::with(['ticketType.event'])->find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'order_status'      => 'in:Cart,Confirmed,Paid,Cancelled',
                'payment_status'    => 'in:Pending,Paid,Failed',
                'quantity'          => 'integer|min:1',
                'price_at_purchase' => 'numeric|min:0',
                'total_amount'      => 'numeric|min:0',
                'purchased_at'      => 'nullable|date',
                'is_scanned'        => 'boolean',
                'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->has('quantity') && $request->quantity != $order->quantity) {
                $ticketType = TicketType::findOrFail($order->ticket_type_id);
                $quantityDifference = $order->quantity - $request->quantity;

                if ($quantityDifference < 0 && $ticketType->quantity_available < abs($quantityDifference)) {
                    return response()->json([
                        'message' => 'Not enough tickets available',
                        'available' => $ticketType->quantity_available
                    ], 400);
                }

                $ticketType->increment('quantity_available', $quantityDifference);
            }

            // Handle file upload
            if ($request->hasFile('image')) {
                if ($order->image) {
                    Storage::delete('public/Order/' . $order->image);
                }
                $order->image = $this->storeOrderImage($request->file('image'));
            }

            $order->update($request->except('image'));

            $updatedOrder = Order::with(['ticketType.event'])->find($id);
            $event = $updatedOrder->ticketType->event ?? null;
            $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $updatedOrder,
                'order_image_url' => $updatedOrder->image ? $this->getOrderImageUrl($updatedOrder->image) : null,
                'event_image' => $eventImageUrl
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // Delete associated image
            if ($order->image) {
                Storage::delete('public/Order/' . $order->image);
            }

            $ticketType = TicketType::findOrFail($order->ticket_type_id);
            $ticketType->increment('quantity_available', $order->quantity);

            $order->delete();

            return response()->json(['message' => 'Order deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByUser($userId)
    {
        try {
            $orders = Order::where('user_id', $userId)
                ->with(['ticketType.event'])
                ->get();

            $formattedOrders = $orders->map(function ($order) {
                $event = $order->ticketType->event ?? null;
                $eventImageUrl = $event && $event->image ? $this->getFullImageUrl($event->image) : null;

                return [
                    'id' => $order->id,
                    'order_date' => $order->order_date ?? $order->created_at->format('Y-m-d H:i:s'),
                    'status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                    'quantity' => $order->quantity,
                    'total' => number_format($order->total_amount, 2),
                    'ticket_type' => [
                        'id' => $order->ticketType->id ?? null,
                        'name' => $order->ticketType->ticket_name ?? null,
                    ],
                    'event' => [
                        'id' => $event->id ?? null,
                        'title' => $event->event_name ?? null,
                        'date' => $event ? date('F j, Y', strtotime($event->event_date)) : null,
                        'image' => $eventImageUrl,
                    ],
                    'order_image' => $order->image ? $this->getOrderImageUrl($order->image) : null,
                ];
            });

            return response()->json($formattedOrders, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getFullImageUrl($path)
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = str_replace('storage/', '', $path);
        return asset('storage/' . $path);
    }

    protected function getOrderImageUrl($filename)
    {
        return asset('storage/Order/' . $filename);
    }

    protected function storeOrderImage($file)
    {
        $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/Order', $filename);
        return $filename;
    }
}
