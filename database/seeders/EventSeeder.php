<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('events')->insert([
            [
                'event_name' => 'Music Festival 2025',
                'image' => 'music_festival.jpg',
                'event_description' => 'Enjoy live music from famous artists.',
                'event_date' => '2025-08-15',
                'start_time' => '18:00:00',
                'end_time' => '23:00:00',
                'event_location' => 'City Stadium',
                'category_id' => 1,
                'user_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'event_name' => 'Tech Conference 2025',
                'image' => 'tech_conference.jpg',
                'event_description' => 'A technology conference with top speakers.',
                'event_date' => '2025-09-20',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'event_location' => 'Convention Center',
                'category_id' => 2,
                'user_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}