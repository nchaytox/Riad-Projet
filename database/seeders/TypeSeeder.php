<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            [
                'name' => 'Patio Double',
                'information' => '24 m², lit queen, patio privatif donnant sur le patio intérieur. Tarif indicatif : 950 000 IDR / nuit pour 2 personnes.',
            ],
            [
                'name' => 'Atlas Suite',
                'information' => 'Suite de 35 m² avec salon séparé, lit king size et terrasse sur le toit. Petit-déjeuner inclus, idéale pour séjours de 3+ nuits.',
            ],
            [
                'name' => 'Family Duplex',
                'information' => 'Deux niveaux, 1 lit king + 2 lits simples, salle d’eau familiale. Capacité 4 personnes, parfait pour voyages en famille.',
            ],
        ];

        foreach ($types as $type) {
            Type::updateOrCreate(
                ['name' => $type['name']],
                ['information' => $type['information']]
            );
        }
    }
}
