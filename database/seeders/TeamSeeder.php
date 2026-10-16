<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Struktura klubu: Seniorzy (otwarci dla każdego zawodnika klubu) oraz po dwie
     * drużyny w każdej kategorii młodzieżowej, rozdzielone rocznikiem urodzenia.
     * Każda drużyna ma trenera głównego i sztab z opisanymi funkcjami.
     */
    public function run(): void
    {
        $people = User::whereIn('email', array_column(UserSeeder::STAFF, 'email'))->get()->keyBy('name');
        $id = fn (string $name) => $people->get($name)?->id;

        $teams = [
            ['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'birth_year' => null,
                'description' => 'Pierwsza drużyna Joker Piła, występująca w 3 lidze. Grać tu może, poza stałymi zawodnikami, każdy wyróżniający się zawodnik klubu.',
                'coach' => 'Tomasz Wiśniewski',
                'staff' => ['Krzysztof Kamiński' => 'Asystent trenera', 'Agnieszka Dąbrowska' => 'Fizjoterapeutka', 'Michał Kozłowski' => 'Statystyk', 'Piotr Mazur' => 'Kierownik drużyny']],
            ['name' => 'Junior 2008', 'slug' => 'junior-2008', 'category' => 'junior', 'birth_year' => 2008,
                'description' => 'Juniorzy z rocznika 2008. Ostatni krok przed seniorską siatkówką.',
                'coach' => 'Katarzyna Zielińska',
                'staff' => ['Paweł Lewandowski' => 'Trener przygotowania motorycznego', 'Agnieszka Dąbrowska' => 'Fizjoterapeutka', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Junior 2009', 'slug' => 'junior-2009', 'category' => 'junior', 'birth_year' => 2009,
                'description' => 'Juniorzy z rocznika 2009. Ambitna grupa walcząca o awans do ligi międzywojewódzkiej.',
                'coach' => 'Katarzyna Zielińska',
                'staff' => ['Krzysztof Kamiński' => 'Asystent trenera', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Junior Młodszy 2010', 'slug' => 'junior-mlodszy-2010', 'category' => 'junior_mlodszy', 'birth_year' => 2010,
                'description' => 'Kadeci z rocznika 2010. Rozwijamy technikę i grę zespołową.',
                'coach' => 'Paweł Lewandowski',
                'staff' => ['Magdalena Wójcik' => 'Asystentka trenera', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Junior Młodszy 2011', 'slug' => 'junior-mlodszy-2011', 'category' => 'junior_mlodszy', 'birth_year' => 2011,
                'description' => 'Kadeci z rocznika 2011. Pierwszy sezon w rozgrywkach wojewódzkich kadetów.',
                'coach' => 'Paweł Lewandowski',
                'staff' => ['Krzysztof Kamiński' => 'Trener przyjęcia i obrony']],
            ['name' => 'Młodzik 2012', 'slug' => 'mlodzik-2012', 'category' => 'mlodzik', 'birth_year' => 2012,
                'description' => 'Młodzicy z rocznika 2012. Siatkówka, która cieszy - dużo gry i dobrej zabawy.',
                'coach' => 'Magdalena Wójcik',
                'staff' => ['Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Młodzik 2013', 'slug' => 'mlodzik-2013', 'category' => 'mlodzik', 'birth_year' => 2013,
                'description' => 'Młodzicy z rocznika 2013 i młodsi. Tu zaczyna się przygoda z Jokerem!',
                'coach' => 'Magdalena Wójcik',
                'staff' => ['Paweł Lewandowski' => 'Asystent trenera']],
        ];

        foreach ($teams as $order => $data) {
            $team = Team::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'category' => $data['category'],
                'birth_year' => $data['birth_year'],
                'trainer_id' => $id($data['coach']),
                'display_order' => $order + 1,
            ]);

            foreach ($data['staff'] as $name => $role) {
                if ($memberId = $id($name)) {
                    $team->assistantTrainers()->attach($memberId, ['role' => $role]);
                }
            }
        }
    }
}
