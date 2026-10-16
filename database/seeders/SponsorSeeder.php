<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use Illuminate\Database\Seeder;

class SponsorSeeder extends Seeder
{
    /**
     * Przykładowi sponsorzy (bez logo - wyświetla się nazwa; logo dodasz w panelu).
     */
    public function run(): void
    {
        $sponsors = [
            ['name' => 'Miasto Piła', 'tier' => 'platinum'],
            ['name' => 'Piekarnia Kłos', 'tier' => 'gold'],
            ['name' => 'AutoSerwis Nowak', 'tier' => 'gold'],
            ['name' => 'Drukarnia Rapid', 'tier' => 'silver'],
            ['name' => 'Fizjo Plus', 'tier' => 'silver'],
            ['name' => 'Budmax', 'tier' => 'bronze'],
        ];

        foreach ($sponsors as $order => $sponsor) {
            Sponsor::create([
                'name' => $sponsor['name'],
                'description' => null,
                'logo_path' => null,
                'website_url' => null,
                'tier' => $sponsor['tier'],
                'display_order' => $order + 1,
                'is_active' => true,
            ]);
        }
    }
}
