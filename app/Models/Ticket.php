<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'order_id',
        'ticket_code',
        'ticket_type_id',
        'user_id',
        'scanned_at',
        'status',
    ];

    // Relationship to OrderDetail
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relationship to TicketType
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Check if the ticket is still valid (active)
    public function isActive()
    {
        return $this->status === 'active';
    }

    // Mark ticket as used (e.g., when scanned at the event)
    public function markAsUsed()
    {
        $this->update(['status' => 'used', 'scanned_at' => now()]);
    }
}
