<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use Illuminate\Database\Seeder;

class SponsorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sponsorzy platynowi
        Sponsor::factory()->count(2)->create([
            'tier' => 'platinum',
            'display_order' => 1,
            'is_active' => true,
        ]);

        // Sponsorzy złoci
        Sponsor::factory()->count(3)->create([
            'tier' => 'gold',
            'display_order' => 2,
            'is_active' => true,
        ]);

        // Sponsorzy srebrni
        Sponsor::factory()->count(5)->create([
            'tier' => 'silver',
            'display_order' => 3,
            'is_active' => true,
        ]);

        // Sponsorzy brązowi
        Sponsor::factory()->count(8)->create([
            'tier' => 'bronze',
            'display_order' => 4,
            'is_active' => true,
        ]);
    }
}
