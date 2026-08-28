<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = User::where('role', UserRole::Admin)->get();

        // Dokumenty publiczne
        Document::factory()->count(15)->create([
            'uploaded_by' => $admins->random()->id,
            'is_public' => true,
        ]);

        // Dokumenty prywatne (tylko dla zalogowanych)
        Document::factory()->count(5)->create([
            'uploaded_by' => $admins->random()->id,
            'is_public' => false,
            'category' => 'regulations',
        ]);
    }
}
