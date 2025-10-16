<?php

namespace Database\Factories;

use App\Models\RoomStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomStatusFactory extends Factory
{
    protected $model = RoomStatus::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'information' => $this->faker->sentence(),
        ];
    }
}
