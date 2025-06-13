<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tickets')->insert([
            [
                'order_id' => 1,
                'ticket_code' => 'TICKET-0001',
                'ticket_type_id' => 1,
                'user_id' => 1,
                'scanned_at' => null,
                'status' => 'active',
                'event_id' => 1,
                'category_id' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'order_id' => 2,
                'ticket_code' => 'TICKET-0002',
                'ticket_type_id' => 2,
                'user_id' => 2,
                'scanned_at' => Carbon::now(),
                'status' => 'used',
                'event_id' => 2,
                'category_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}