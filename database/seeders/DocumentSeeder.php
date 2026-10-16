<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    /**
     * Przykładowe dokumenty do pobrania (proste pliki tekstowe zamiast prawdziwych PDF-ów).
     */
    public function run(): void
    {
        $adminId = User::where('role', UserRole::Admin)->value('id');

        $documents = [
            ['Regulamin klubu Joker Piła', 'regulations', 'Zasady uczestnictwa w treningach i rozgrywkach.'],
            ['Regulamin hali sportowej', 'regulations', 'Zasady korzystania z hali przy ul. Sportowej 1.'],
            ['Deklaracja członkowska', 'forms', 'Wypełnij przy zapisie zawodnika do klubu.'],
            ['Zgoda rodzica na udział w zajęciach', 'forms', 'Wymagana dla zawodników niepełnoletnich.'],
            ['Zgoda na publikację wizerunku (RODO)', 'forms', 'Zdjęcia z meczów i treningów na stronie i w mediach klubu.'],
            ['Sprawozdanie z działalności za ubiegły sezon', 'reports', 'Podsumowanie wyników sportowych i finansów klubu.'],
        ];

        foreach ($documents as [$title, $category, $description]) {
            $path = 'documents/'.Str::slug($title).'.txt';
            Storage::disk('public')->put($path, $title."\n\n".$description."\n\n(Przykładowy dokument - podmień go w panelu admina.)\n");

            Document::create([
                'title' => $title,
                'description' => $description,
                'file_path' => $path,
                'file_type' => 'txt',
                'file_size' => Storage::disk('public')->size($path),
                'category' => $category,
                'uploaded_by' => $adminId,
                'download_count' => 0,
                'is_public' => true,
            ]);
        }
    }
}
