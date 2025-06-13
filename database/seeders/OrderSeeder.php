<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('orders')->insert([
            [
                'user_id' => 1,
                'order_status' => 'Confirmed',
                'order_date' => Carbon::now(),
                'total_amount' => 100.00,
                'payment_status' => 'Paid',
                'purchased_at' => Carbon::now(),
                'ticket_type_id' => 1,
                'quantity' => 2,
                'price_at_purchase' => 50.00,
                'qr_code' => 'QR-ORDER-0001',
                'is_scanned' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 2,
                'order_status' => 'Paid',
                'order_date' => Carbon::now(),
                'total_amount' => 150.00,
                'payment_status' => 'Paid',
                'purchased_at' => Carbon::now(),
                'ticket_type_id' => 2,
                'quantity' => 3,
                'price_at_purchase' => 50.00,
                'qr_code' => 'QR-ORDER-0002',
                'is_scanned' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}