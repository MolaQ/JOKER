<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Drużyny klubu zgłoszone w WZPS (lista: wzps.poznan.pl/obsady/index.php?mode=8&club_id=7).
     * Każda drużyna ma trenera głównego i sztab z opisanymi funkcjami.
     */
    public function run(): void
    {
        $people = User::whereIn('email', array_column(UserSeeder::STAFF, 'email'))->get()->keyBy('name');
        $id = fn (string $name) => $people->get($name)?->id;

        $teams = [
            ['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'birth_year' => null,
                'description' => 'Pierwsza drużyna Joker Piła, występująca w II lidze mężczyzn. Grać tu może, poza stałymi zawodnikami, każdy wyróżniający się zawodnik klubu.',
                'coach' => 'Tomasz Wiśniewski',
                'staff' => ['Krzysztof Kamiński' => 'Asystent trenera', 'Agnieszka Dąbrowska' => 'Fizjoterapeutka', 'Michał Kozłowski' => 'Statystyk', 'Piotr Mazur' => 'Kierownik drużyny']],
            ['name' => 'Juniorzy', 'slug' => 'juniorzy', 'category' => 'junior', 'birth_year' => 2008,
                'description' => 'Pierwsza drużyna juniorów. Ostatni krok przed seniorską siatkówką.',
                'coach' => 'Katarzyna Zielińska',
                'staff' => ['Paweł Lewandowski' => 'Trener przygotowania motorycznego', 'Agnieszka Dąbrowska' => 'Fizjoterapeutka', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Juniorzy II', 'slug' => 'juniorzy-ii', 'category' => 'junior', 'birth_year' => 2009,
                'description' => 'Druga drużyna juniorów. Gra też w III lidze mężczyzn jako „LOMS Joker Piła 1”.',
                'coach' => 'Katarzyna Zielińska',
                'staff' => ['Krzysztof Kamiński' => 'Asystent trenera', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'junior_mlodszy', 'birth_year' => 2010,
                'description' => 'Pierwsza drużyna kadetów. W III lidze mężczyzn występuje jako „LOMS Joker Piła 2”.',
                'coach' => 'Paweł Lewandowski',
                'staff' => ['Magdalena Wójcik' => 'Asystentka trenera', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Kadeci II', 'slug' => 'kadeci-ii', 'category' => 'junior_mlodszy', 'birth_year' => 2011,
                'description' => 'Druga drużyna kadetów. Zawodnicy są wypożyczeni do klubu KS ASBL Piła i w Lidze Kadetów grają jako ASBL.',
                'coach' => 'Paweł Lewandowski',
                'staff' => ['Krzysztof Kamiński' => 'Trener przyjęcia i obrony']],
            ['name' => 'Młodzicy', 'slug' => 'mlodzicy', 'category' => 'mlodzik', 'birth_year' => 2012,
                'description' => 'Pierwsza drużyna młodzików. Siatkówka, która cieszy - dużo gry i dobrej zabawy.',
                'coach' => 'Magdalena Wójcik',
                'staff' => ['Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Młodzicy II', 'slug' => 'mlodzicy-ii', 'category' => 'mlodzik', 'birth_year' => 2013,
                'description' => 'Druga drużyna młodzików. Tu zaczyna się przygoda z Jokerem!',
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
