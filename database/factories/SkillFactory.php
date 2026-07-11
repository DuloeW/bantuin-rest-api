<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SkillFactory extends Factory
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