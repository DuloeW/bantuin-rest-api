<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected static array $categories = [
        'Bersih-Bersih Rumah',
        'Perbaikan & Pertukangan',
        'Jasa Antar & Logistik',
        'Perawatan Taman',
        'Asisten Pribadi',
        'Desain Grafis',
        'Servis AC & Elektronik',
        'Pengasuh Anak',
        'Perawatan Hewan',
        'Les & Tutor Privat',
        'Fotografi & Videografi',
        'Cuci Sepatu & Tas',
        'Penerjemah Dokumen',
        'Katering & Masak',
        'Laundry & Setrika',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->randomElement(self::$categories);

        return [
            'id' => Str::uuid(),
            'title' => $title,
            'slug' => Str::slug($title),
        ];
    }
}
