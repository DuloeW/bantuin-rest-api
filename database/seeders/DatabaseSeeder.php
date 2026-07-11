<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Category::truncate();
        Skill::truncate();
        Schema::enableForeignKeyConstraints();

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'super',
                'last_name' => 'admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $skills = [
            'Technology',
            'Design',
            'Writing',
            'Business',
            'Education',
            'Health',
            'Home Services',
            'Logistics',
            'Events',
            'Other',
        ];

        $categories = [
            'Technology',
            'Design',
            'Writing',
            'Business',
            'Education',
            'Health',
            'Home Services',
            'Logistics',
            'Events',
            'Other',
        ];

        foreach ($skills as $skill) {
            Skill::create([
                'title' => $skill,
                'slug'  => Str::slug($skill),
            ]);
        }

        foreach ($categories as $category) {
            Category::create([
                'title' => $category,
                'slug'  => Str::slug($category),
            ]);
        }
    }
}
