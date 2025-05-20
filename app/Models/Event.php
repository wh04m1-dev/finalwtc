<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'event_name',
        'image',
        'event_description',
        'event_date',
        'start_time',
        'end_time',
        'event_location',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'event_id');
    }

    public function ticketTypes()
    {
        return $this->hasMany(TicketType::class, 'event_id');
    }
}
