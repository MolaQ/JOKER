<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Document;
use App\Models\HeroSlide;
use App\Models\Page;
use App\Models\Sponsor;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Treści strony: aktualności z komentarzami, dokumenty, sponsorzy, slajdy i podstrony.
 */
class ContentSeeder extends Seeder
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
        $this->seedArticles();
        $this->seedDocuments();
        $this->seedSponsors();
        $this->seedHeroSlides();
        $this->seedPages();
    }

    private function seedArticles(): void
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

    /**
     * Przykładowe dokumenty do pobrania (proste pliki tekstowe zamiast prawdziwych PDF-ów).
     */
    private function seedDocuments(): void
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

    /**
     * Przykładowi sponsorzy (bez logo - wyświetla się nazwa; logo dodasz w panelu).
     */
    private function seedSponsors(): void
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

    /**
     * Slajdy na stronie głównej (zdjęcia z Unsplash - patrz config/media.php).
     */
    private function seedHeroSlides(): void
    {
        $slides = [
            ['title' => 'Gramy razem. Wygrywamy razem.', 'subtitle' => 'Klub siatkarski z Piły. Od pierwszego odbicia młodzika po mecze seniorów w 3 lidze.',
                'photo' => 'hero-team', 'cta_label' => 'Poznaj drużyny', 'cta_url' => '/teams'],
            ['title' => 'Nabór trwa cały rok', 'subtitle' => 'Pierwszy trening za darmo. Zapraszamy dziewczęta i chłopców od 8 lat.',
                'photo' => 'men-game-1', 'cta_label' => 'Zapisz się', 'cta_url' => '/contact'],
            ['title' => 'Każdy punkt to praca całej drużyny', 'subtitle' => 'Sprawdź terminarz i przyjdź nas wspierać na hali.',
                'photo' => 'spike', 'cta_label' => 'Terminarz', 'cta_url' => '/schedule'],
        ];

        foreach ($slides as $order => $slide) {
            HeroSlide::create([
                'title' => $slide['title'],
                'subtitle' => $slide['subtitle'],
                'image_path' => config("media.photos.{$slide['photo']}.url"),
                'cta_label' => $slide['cta_label'],
                'cta_url' => $slide['cta_url'],
                'display_order' => $order + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Podstrony podlinkowane w górnym pasku i menu (edytowalne w Panel → Podstrony).
     */
    private function seedPages(): void
    {
        $pages = [
            [
                'title' => 'O klubie',
                'slug' => 'o-klubie',
                'lead' => 'Joker Piła to klub, w którym siatkówka łączy pokolenia: od dzieci, które pierwszy raz odbijają piłkę, po seniorów walczących o ligowe punkty.',
                'content' => '<h2>Nasza historia</h2><p>Zaczynaliśmy od jednej grupy treningowej w szkolnej sali. Dziś w klubie trenuje kilkuset zawodniczek i zawodników w siedmiu drużynach, a nasi wychowankowie grają w ligach centralnych.</p><h2>Co jest dla nas ważne</h2><ul><li><strong>Drużyna ponad wszystko</strong> - wygrywamy i przegrywamy razem.</li><li><strong>Szkolenie</strong> - licencjonowani trenerzy i jasna ścieżka od młodzika do seniora.</li><li><strong>Fair play</strong> - szacunek dla rywala, sędziego i kibica.</li></ul><h2>Hala</h2><p>Mecze domowe rozgrywamy w hali przy ul. Sportowej 1 w Pile. Wstęp na mecze młodzieżowe jest bezpłatny.</p>',
            ],
            [
                'title' => 'Dla rodziców',
                'slug' => 'dla-rodzicow',
                'lead' => 'Wszystko, co rodzic młodego siatkarza powinien wiedzieć: zapisy, składki, wyjazdy i kontakt z trenerem.',
                'content' => '<h2>Jak zapisać dziecko?</h2><ol><li>Napisz lub zadzwoń do nas przez stronę Kontakt.</li><li>Przyjdź na bezpłatny trening próbny.</li><li>Wypełnij deklarację członkowską i zgodę rodzica (znajdziesz je w Dokumentach).</li></ol><h2>Składki</h2><p>Składka członkowska wynosi 120 zł miesięcznie. Rodzeństwo płaci 50% składki za drugie dziecko.</p><h2>Badania lekarskie</h2><p>Każdy zawodnik musi mieć aktualne badania sportowe. Pomożemy umówić wizytę w przychodni sportowej.</p><h2>Wyjazdy na mecze</h2><p>Na mecze wyjazdowe jeździmy autokarem klubowym. Informacje o zbiórce trener przekazuje w grupie drużyny.</p>',
            ],
            [
                'title' => 'Dla kibiców',
                'slug' => 'dla-kibicow',
                'lead' => 'Najlepsza atmosfera w hali to Wasza zasługa. Zobacz, jak wspierać Jokera.',
                'content' => '<h2>Bilety</h2><p>Wstęp na mecze młodzieżowe jest bezpłatny. Na mecze seniorów obowiązują bilety: normalny 15 zł, ulgowy 10 zł, dzieci do lat 10 wchodzą za darmo.</p><h2>Gdzie oglądać?</h2><p>Wybrane mecze transmitujemy na żywo na naszym profilu w mediach społecznościowych. Terminarz z godzinami znajdziesz w zakładce Mecze.</p><h2>Klub kibica</h2><p>Chcesz pomagać przy oprawie meczów albo jeździć z drużyną na wyjazdy? Napisz do nas!</p>',
            ],
            [
                'title' => 'Nabór',
                'slug' => 'nabor',
                'lead' => 'Szukamy dziewcząt i chłopców od 8. roku życia. Pierwszy trening jest za darmo, a sprzęt na start pożyczymy.',
                'content' => '<h2>Grupy wiekowe</h2><ul><li><strong>Mini-siatkówka</strong> - 8-11 lat, 2 treningi w tygodniu.</li><li><strong>Młodzicy</strong> - 12-13 lat, 3 treningi w tygodniu.</li><li><strong>Kadeci i juniorzy</strong> - 14-19 lat, 4 treningi w tygodniu.</li></ul><h2>Co zabrać na pierwszy trening?</h2><p>Strój sportowy, halowe buty, butelkę wody i dobry humor.</p><h2>Jak się zapisać?</h2><p>Skorzystaj z formularza na stronie Kontakt albo po prostu przyjdź na trening - zapisy trwają cały rok.</p>',
            ],
            [
                'title' => 'FAQ - najczęstsze pytania',
                'slug' => 'faq',
                'lead' => 'Odpowiedzi na pytania, które słyszymy najczęściej.',
                'content' => '<h3>Czy dziecko musi umieć grać w siatkówkę?</h3><p>Nie. Na pierwszych treningach uczymy podstaw od zera.</p><h3>Ile kosztują treningi?</h3><p>Pierwszy trening jest bezpłatny. Później obowiązuje składka członkowska opisana w zakładce Dla rodziców.</p><h3>Gdzie odbywają się treningi?</h3><p>W hali przy ul. Sportowej 1 w Pile oraz w wybranych salach szkolnych. Szczegóły podaje trener grupy.</p><h3>Jak dostać się na mecz?</h3><p>Terminarz z godzinami i adresami hal znajdziesz w zakładce Mecze. Każdy mecz możesz dodać do swojego kalendarza.</p><h3>Jak zostać sponsorem?</h3><p>Napisz do nas przez stronę Kontakt, przygotujemy ofertę współpracy.</p>',
            ],
        ];

        foreach ($pages as $order => $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page + ['display_order' => $order, 'is_published' => true]);
        }
    }
}
