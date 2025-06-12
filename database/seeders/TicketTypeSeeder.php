<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ticket_types')->insert([
            [
                'event_id' => 1,
                'ticket_name' => 'VIP',
                'price' => 100.00,
                'quantity_available' => 50,
                'discount' => 10.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'event_id' => 1,
                'ticket_name' => 'General Admission',
                'price' => 50.00,
                'quantity_available' => 200,
                'discount' => 5.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}