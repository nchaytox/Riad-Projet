<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Type;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = Type::query()->pluck('id', 'name');
        $statuses = RoomStatus::query()->pluck('id', 'code');

        $rooms = [
            [
                'number' => '101',
                'type' => 'Patio Double',
                'status' => 'AVL',
                'capacity' => 2,
                'price' => 950000,
                'view' => 'Rez-de-chaussée lumineux, patio privatif et décoration artisanale.',
            ],
            [
                'number' => '102',
                'type' => 'Patio Double',
                'status' => 'CLN',
                'capacity' => 2,
                'price' => 950000,
                'view' => 'Même configuration que 101, actuellement en nettoyage.',
            ],
            [
                'number' => '201',
                'type' => 'Atlas Suite',
                'status' => 'AVL',
                'capacity' => 3,
                'price' => 1650000,
                'view' => 'Suite à l’étage avec terrasse panoramique sur la médina.',
            ],
            [
                'number' => '202',
                'type' => 'Atlas Suite',
                'status' => 'OCC',
                'capacity' => 3,
                'price' => 1650000,
                'view' => 'Suite occupée, terrasse privative et salon séparé.',
            ],
            [
                'number' => '301',
                'type' => 'Family Duplex',
                'status' => 'AVL',
                'capacity' => 4,
                'price' => 2150000,
                'view' => 'Duplex familial avec mezzanine enfants et salon voûté.',
            ],
            [
                'number' => '302',
                'type' => 'Family Duplex',
                'status' => 'OOS',
                'capacity' => 4,
                'price' => 2150000,
                'view' => 'Duplex en maintenance préventive (climatisation).',
            ],
        ];

        foreach ($rooms as $room) {
            $typeId = $types[$room['type']] ?? null;
            $statusId = $statuses[$room['status']] ?? null;

            if (! $typeId || ! $statusId) {
                continue;
            }

            Room::updateOrCreate(
                ['number' => $room['number']],
                [
                    'type_id' => $typeId,
                    'room_status_id' => $statusId,
                    'capacity' => $room['capacity'],
                    'price' => $room['price'],
                    'view' => $room['view'],
                ]
            );
        }
    }
}
