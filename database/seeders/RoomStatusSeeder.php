<?php

namespace Database\Seeders;

use App\Models\RoomStatus;
use Illuminate\Database\Seeder;

class RoomStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statuses = [
            [
                'code' => 'AVL',
                'name' => 'Available',
                'information' => 'Prête à être vendue et impeccablement préparée pour un nouvel invité.',
            ],
            [
                'code' => 'OCC',
                'name' => 'Occupied',
                'information' => 'Client actuellement en séjour dans la chambre.',
            ],
            [
                'code' => 'CLN',
                'name' => 'Housekeeping',
                'information' => 'En nettoyage ou inspection, revente possible dans l’heure.',
            ],
            [
                'code' => 'OOS',
                'name' => 'Out of Service',
                'information' => 'Maintenance nécessaire, chambre retirée de l’inventaire.',
            ],
        ];

        foreach ($statuses as $status) {
            RoomStatus::updateOrCreate(
                ['code' => $status['code']],
                [
                    'name' => $status['name'],
                    'information' => $status['information'],
                ]
            );
        }
    }
}
