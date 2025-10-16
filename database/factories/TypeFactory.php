<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeFactory extends Factory
{
    protected $model = Type::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true).' Suite',
            'information' => $this->faker->sentence(12),
        ];
    }
}
