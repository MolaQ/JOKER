<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Struktura klubu: Seniorzy (otwarci dla każdego zawodnika klubu) oraz po dwie
     * drużyny w każdej kategorii młodzieżowej, rozdzielone rocznikiem urodzenia.
     */
    public function run(): void
    {
        $trainers = User::where('role', UserRole::Trainer)->get();

        $teams = [
            ['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'birth_year' => null, 'order' => 1,
                'description' => 'Pierwsza drużyna Joker Piła. Grać tu może, poza stałymi zawodnikami, każdy zawodnik klubu.'],
            ['name' => 'Junior 2008', 'slug' => 'junior-2008', 'category' => 'junior', 'birth_year' => 2008, 'order' => 2,
                'description' => 'Drużyna juniorów, rocznik 2008.'],
            ['name' => 'Junior 2009', 'slug' => 'junior-2009', 'category' => 'junior', 'birth_year' => 2009, 'order' => 3,
                'description' => 'Drużyna juniorów, rocznik 2009.'],
            ['name' => 'Junior Młodszy 2010', 'slug' => 'junior-mlodszy-2010', 'category' => 'junior_mlodszy', 'birth_year' => 2010, 'order' => 4,
                'description' => 'Drużyna juniorów młodszych, rocznik 2010.'],
            ['name' => 'Junior Młodszy 2011', 'slug' => 'junior-mlodszy-2011', 'category' => 'junior_mlodszy', 'birth_year' => 2011, 'order' => 5,
                'description' => 'Drużyna juniorów młodszych, rocznik 2011.'],
            ['name' => 'Młodzik 2012', 'slug' => 'mlodzik-2012', 'category' => 'mlodzik', 'birth_year' => 2012, 'order' => 6,
                'description' => 'Drużyna młodzików, rocznik 2012.'],
            ['name' => 'Młodzik 2013', 'slug' => 'mlodzik-2013', 'category' => 'mlodzik', 'birth_year' => 2013, 'order' => 7,
                'description' => 'Drużyna młodzików, rocznik 2013 i młodsi.'],
        ];

        foreach ($teams as $i => $team) {
            $created = Team::create([
                'name' => $team['name'],
                'slug' => $team['slug'],
                'description' => $team['description'],
                'category' => $team['category'],
                'birth_year' => $team['birth_year'],
                'trainer_id' => $trainers->isNotEmpty() ? $trainers[$i % $trainers->count()]->id : null,
                'display_order' => $team['order'],
            ]);

            // Przykładowy trener pomocniczy (dodatkowy) dla pierwszej drużyny.
            if ($i === 0 && $trainers->count() > 1) {
                $created->assistantTrainers()->attach($trainers[1]->id);
            }
        }
    }
}
