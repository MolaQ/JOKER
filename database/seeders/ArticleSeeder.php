<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Przykładowe aktualności (zdjęcia zastępcze dobiera strona - patrz config/media.php).
     *
     * @var array<int, array{title: string, excerpt: string, paragraphs: array<int, string>, featured?: bool}>
     */
    private const ARTICLES = [
        ['title' => 'Seniorzy wygrywają 3:1 na inaugurację sezonu', 'featured' => true,
            'excerpt' => 'Pełna hala, świetna atmosfera i pierwsze trzy punkty na koncie. Tak zaczęliśmy nowy sezon w 3 lidze.',
            'paragraphs' => [
                'Od pierwszej piłki było widać, że drużyna jest dobrze przygotowana. Mocna zagrywka i skuteczny blok pozwoliły wygrać pierwszego seta 25:19.',
                'W drugiej partii rywale wrócili do gry, ale w trzecim i czwartym secie nasi siatkarze nie dali im szans. MVP spotkania został nasz rozgrywający.',
                'Dziękujemy kibicom za fantastyczny doping. Kolejny mecz już za tydzień na wyjeździe.',
            ]],
        ['title' => 'Nabór do grup młodzieżowych - zapraszamy na trening próbny',
            'excerpt' => 'Szukamy dziewcząt i chłopców w wieku 8-14 lat. Pierwszy trening jest bezpłatny, sprzęt na start pożyczamy.',
            'paragraphs' => [
                'Treningi odbywają się w poniedziałki, środy i piątki w Hali Sportowej przy ul. Sportowej 1.',
                'Nie musisz mieć doświadczenia - nauczymy wszystkiego od podstaw. Wystarczą sportowe buty, strój i chęć do gry.',
                'Aby zapisać dziecko, wypełnij formularz na stronie Kontakt albo po prostu przyjdź na halę 15 minut przed treningiem.',
            ]],
        ['title' => 'Junior 2008 w finale wojewódzkim',
            'excerpt' => 'Nasi juniorzy awansowali do turnieju finałowego mistrzostw województwa. Trzymamy kciuki!',
            'paragraphs' => [
                'Po trzech zwycięstwach w turnieju półfinałowym drużyna trenerki Katarzyny Zielińskiej zameldowała się w najlepszej czwórce województwa.',
                'Finał odbędzie się w przyszły weekend. Zapraszamy wszystkich kibiców - wstęp wolny.',
            ]],
        ['title' => 'Obóz letni 2026 - zapisy otwarte',
            'excerpt' => 'Dziesięć dni siatkówki, plaży i dobrej zabawy nad morzem. Liczba miejsc ograniczona.',
            'paragraphs' => [
                'Tegoroczny obóz odbędzie się w Łebie. W planie dwa treningi dziennie, siatkówka plażowa, zajęcia z przygotowania motorycznego i mnóstwo atrakcji.',
                'Szczegóły i formularz zgłoszeniowy znajdziesz u trenerów grup lub w sekretariacie klubu.',
            ]],
        ['title' => 'Nowy sponsor techniczny klubu',
            'excerpt' => 'Od tego sezonu wszystkie drużyny grają w nowych strojach. Dziękujemy partnerowi za zaufanie!',
            'paragraphs' => [
                'Nowe komplety meczowe otrzymały wszystkie drużyny - od młodzików po seniorów. Stroje nawiązują do barw herbu: grafitu i błękitu.',
                'Partner wspiera także klubową akademię, co pozwoli nam zorganizować więcej turniejów dla najmłodszych.',
            ]],
        ['title' => 'Młodzicy najlepsi w turnieju o Puchar Prezydenta Piły',
            'excerpt' => 'Złoto dla Młodzika 2012 i brąz dla Młodzika 2013. Wspaniały weekend w naszej hali!',
            'paragraphs' => [
                'W turnieju wzięło udział osiem drużyn z całej Wielkopolski. Nasi młodzicy nie przegrali ani jednego seta.',
                'Gratulujemy zawodnikom i trenerce Magdalenie Wójcik. Dziękujemy rodzicom za pomoc w organizacji.',
            ]],
        ['title' => 'Jak dbać o regenerację? Porady naszej fizjoterapeutki',
            'excerpt' => 'Sen, nawodnienie i rozciąganie - Agnieszka Dąbrowska podpowiada, jak uniknąć kontuzji.',
            'paragraphs' => [
                'Najważniejszy jest sen: młody sportowiec potrzebuje 8-10 godzin. To w nocy organizm odbudowuje mięśnie.',
                'Po treningu warto poświęcić 10 minut na spokojne rozciąganie i pamiętać o wodzie przez cały dzień, nie tylko na hali.',
                'Jeśli coś boli dłużej niż kilka dni - zgłoś się do trenera. Lepiej zapobiegać niż leczyć.',
            ]],
        ['title' => 'Kadeci rozpoczynają sezon ligowy',
            'excerpt' => 'Obie drużyny juniorów młodszych zagrają w lidze wojewódzkiej. Przed nami pierwsze mecze.',
            'paragraphs' => [
                'Trener Paweł Lewandowski przygotowywał zespoły przez całe lato. Nacisk położono na przyjęcie zagrywki i grę w obronie.',
                'Terminarz wszystkich spotkań znajdziesz w zakładce Terminarz.',
            ]],
        ['title' => 'Dzień otwarty klubu - przyjdź i spróbuj siatkówki',
            'excerpt' => 'Pokazowe treningi, konkursy, spotkanie z seniorami i grill dla całych rodzin.',
            'paragraphs' => [
                'Zapraszamy w sobotę od 10:00 do hali przy ul. Sportowej. Każdy może wejść na boisko i spróbować swoich sił.',
                'Na najmłodszych czekają nagrody i pamiątkowe zdjęcia z zawodnikami pierwszej drużyny.',
            ]],
    ];

    public function run(): void
    {
        $authors = User::whereIn('role', [UserRole::Admin, UserRole::Trainer])->get();
        $commenters = User::whereIn('role', [UserRole::Player, UserRole::Fan, UserRole::Parent])->get();

        foreach (self::ARTICLES as $index => $data) {
            $article = Article::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'excerpt' => $data['excerpt'],
                'content' => collect($data['paragraphs'])->map(fn (string $paragraph) => "<p>{$paragraph}</p>")->implode("\n"),
                'featured_image' => null,
                'author_id' => $authors->random()->id,
                'status' => 'published',
                'published_at' => now()->subDays($index * 4 + 1)->setTime(18, 0),
                'views_count' => fake()->numberBetween(80, 2400),
                'is_featured' => $data['featured'] ?? false,
                'allow_comments' => true,
            ]);

            Comment::factory()
                ->count(fake()->numberBetween(1, 4))
                ->create([
                    'article_id' => $article->id,
                    'user_id' => fn () => $commenters->random()->id,
                    'is_approved' => true,
                ]);
        }
    }
}
