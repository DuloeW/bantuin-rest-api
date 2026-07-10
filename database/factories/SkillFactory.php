<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected static array $skills = [
        'Pembersihan Rumah & Apartemen',
        'Cuci AC & Perawatan',
        'Instalasi Listrik',
        'Perbaikan Kran & Pipa Air',
        'Pengecatan Dinding & Kusen',
        'Desain Logo & Banner',
        'Edit Foto & Video',
        'Fotografi Event',
        'Mengemudi & Antar Jemput',
        'Packing & Pindahan',
        'Menulis Artikel & Konten',
        'Terjemahan Bahasa Inggris',
        'Pemrograman Website',
        'Servis Handphone & Laptop',
        'Perawatan Kucing & Anjing',
        'Grooming Hewan Peliharaan',
        'Pengajaran Matematika',
        'Pengajaran Bahasa Asing',
        'Memasak Makanan Nusantara',
        'Pembuatan Kue & Pastry',
        'Setrika Baju & Lipat',
        'Cuci Sepatu & Restorasi',
        'Make Up Artist (MUA)',
        'Pijat Tradisional & Refleksi',
        'Pemasangan Wifi & Router',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->randomElement(self::$skills);

        return [
            'id' => Str::uuid(),
            'title' => $title,
            'slug' => Str::slug($title),
        ];
    }
}
