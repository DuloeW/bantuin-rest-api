<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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

        Category::factory()->count(15)->create();
        Skill::factory()->count(25)->create();
    }
}
