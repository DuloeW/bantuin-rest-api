<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'title' => $this->faker->word(),
            'slug' => fn (array $attributes) => Str::slug($attributes['title']), 
        ];
    }
}