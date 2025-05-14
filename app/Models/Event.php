<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'event_name',
        'event_description',
        'event_date',
        'event_location',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
