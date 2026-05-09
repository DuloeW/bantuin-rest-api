<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    return [
        'id' => \Illuminate\Support\Str::uuid(), // Meng-generate UUID
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->numerify('08##########'), // Bikin nomor HP random
        'password' => bcrypt('password'), // Biar gampang tes login, passwordnya 'password' semua
        'role' => 'user', // Set otomatis sebagai user biasa
        'province' => 'bali',
        'district' => 'badung',
        'sub_district' => 'kuta selatan',
        'village' => 'jimbaran',
        'neighborhood_unit' => '0',
        'wallet_balance' => 0,
        'is_verified' => 1,
        'status' => 'active',
    ];
}

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
