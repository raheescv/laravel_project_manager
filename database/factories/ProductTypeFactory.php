<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name().'-'.time().'-'.rand(10, 1000),
        ];
    }
}
