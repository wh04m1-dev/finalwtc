<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'event_id',
        'discount_code',
        'discount_percentage',
        'start_date',
        'end_date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function isActive()
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }
}
