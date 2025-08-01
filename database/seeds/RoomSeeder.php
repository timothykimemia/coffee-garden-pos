<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run()
    {
        $rooms = [
            ['room_number' => '101', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '102', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '103', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '104', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '105', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '106', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '107', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '108', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '201', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '202', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '203', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '204', 'type' => 'Double Standard', 'price' => 5000],
            ['room_number' => '205', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '206', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '207', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '208', 'type' => 'Single', 'price' => 4000],
            ['room_number' => '209', 'type' => 'Deluxe', 'price' => 7000],
            ['room_number' => '210', 'type' => 'Executive', 'price' => 12000],
            ['room_number' => '211', 'type' => 'Executive', 'price' => 12000],
            ['room_number' => '212', 'type' => 'Deluxe', 'price' => 7000],
        ];

        foreach ($rooms as $room) {
            Room::create([
                'business_id' => 1, // Adjust based on your business ID
                'room_number' => $room['room_number'],
                'type' => $room['type'],
                'price' => $room['price'],
                'is_available' => true,
            ]);
        }
    }
}