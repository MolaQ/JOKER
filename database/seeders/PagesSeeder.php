<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Podstrony z treścią: stałe opisy klubu (z prezentacji klubu, 2026) i tymczasowe teksty do uzupełnienia w panelu.
 * Istniejące podstrony o tym samym adresie są nadpisywane tylko przy pierwszym uruchomieniu (gdy ich nie ma).
 */
class PagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::pages() as $order => $page) {
            Page::firstOrCreate(['slug' => $page['slug']], $page + ['display_order' => $order, 'is_published' => true]);
        }
    }

    /**
     * @return list<array{title: string, slug: string, lead: string, content: string}>
     */
    public static function pages(): array
    {
        return [
            [
                'title' => 'O klubie',
                'slug' => 'o-klubie',
                'lead' => 'Każdego dnia około 100 młodych ludzi z Piły, regionu i różnych stron Polski rozwija u nas swoje siatkarskie pasje i umiejętności.',
                'content' => '<h2>Historia</h2>'
                    .'<p>Joker Piła to klub, którego historia sięga 1992 roku. Obecny UKS SMS Joker Piła, powstały w 2009 roku, kontynuuje tradycje i dorobek sportowy swojego poprzednika.</p>'
                    .'<p>Od wielu lat należymy do czołowych ośrodków szkolenia siatkówki młodzieżowej w skali całego kraju. Potwierdzają to wyniki sportowe, regularne awanse do turniejów finałowych mistrzostw Polski, zdobyte w 2026 roku Mistrzostwo Polski w minisiatkówce oraz systematyczne powołania naszych zawodników do młodzieżowych reprezentacji Polski.</p>'
                    .'<h2>II liga mężczyzn</h2>'
                    .'<p>Od sezonu 2025/2026 uczestniczymy również w rozgrywkach II Ligi Mężczyzn Polskiego Związku Piłki Siatkowej, w których nasza najzdolniejsza młodzież zdobywa cenne doświadczenie, a mecze ligowe cieszą się dużym zainteresowaniem lokalnej społeczności.</p>'
                    .'<p>Każdy sezon przynosi kolejne sukcesy naszych drużyn i wychowanków, będące efektem konsekwentnie realizowanego programu szkolenia oraz zaangażowania trenerów, zawodników i całego środowiska skupionego wokół naszego klubu.</p>'
                    .'<h2>Nasi wychowankowie</h2>'
                    .'<p>Największym sukcesem naszego klubu są zawodnicy, którzy dzięki wieloletniemu procesowi szkolenia trafiają na najwyższy poziom sportowy. Wśród naszych wychowanków i byłych zawodników znajdują się reprezentanci Polski, zawodnicy PlusLigi oraz innych szczebli rozgrywek centralnych.</p>',
            ],
            [
                'title' => 'System szkolenia',
                'slug' => 'system-szkolenia',
                'lead' => 'Szkolimy chłopców we wszystkich kategoriach wiekowych: od minisiatkówki po seniorskie rozgrywki III i II Ligi Mężczyzn.',
                'content' => '<p>System szkolenia opiera się na modelu piramidy szkoleniowej: od poziomu podstawowego po specjalistyczny trening wyczynowy. W seniorskich rozgrywkach nasza najstarsza i najzdolniejsza młodzież zdobywa cenne doświadczenie.</p>'
                    .'<h2>Szkolimy w każdej kategorii wiekowej</h2>'
                    .'<ul>'
                    .'<li><strong>Minisiatkówka</strong> (7-13 lat): pierwsze kroki w nauce.</li>'
                    .'<li><strong>Młodzik</strong> (14-15 lat): rozwój nabytych umiejętności.</li>'
                    .'<li><strong>Junior młodszy</strong> (16-17 lat): szkolenie ukierunkowane na zdolności.</li>'
                    .'<li><strong>Junior</strong> (18-19 lat): przygotowanie do poziomu senior.</li>'
                    .'<li><strong>III Liga Mężczyzn</strong> (18+): rozgrywki wojewódzkie WZPS.</li>'
                    .'<li><strong>II Liga Mężczyzn</strong> (18+): rozgrywki centralne PZPS.</li>'
                    .'</ul>'
                    .'<h2>Klub w liczbach</h2>'
                    .'<ul><li>ponad 100 zawodników</li><li>10 trenerów</li><li>8 grup szkoleniowych</li><li>5-7 zajęć w tygodniu</li></ul>'
                    .'<h2>Młodzieżowy Ośrodek Siatkarski</h2>'
                    .'<p>Jako Młodzieżowy Ośrodek Siatkarski, tworzony od poziomu juniora młodszego wspólnie z LO Mistrzostwa Sportowego w Piłce Siatkowej w Pile, od trzech lat otrzymujemy certyfikat Brązowej Odznaki PZPS. W 2026 roku do Odznaki Srebrnej zabrakło nam zaledwie jednego punktu, co potwierdza stały rozwój sportowy i organizacyjny klubu.</p>'
                    .'<h2>Wychowujemy, szkolimy, rozwijamy pasję</h2>'
                    .'<h3>Popołudnia wypełnione treningami</h3>'
                    .'<ul><li>regularne zajęcia sportowe</li><li>nowoczesne metody szkolenia</li><li>rozwój techniczny, merytoryczny oraz mentalny</li><li>indywidualne podejście do zawodnika</li></ul>'
                    .'<h3>Weekendy pełne sportowych emocji</h3>'
                    .'<ul><li>mecze ligowe i turnieje</li><li>rywalizacja w Pile, Wielkopolsce oraz całej Polsce</li><li>dziesiątki wyjazdów w sezonie</li><li>budowanie charakteru i zespołu</li></ul>',
            ],
            [
                'title' => 'Osiągnięcia',
                'slug' => 'osiagniecia',
                'lead' => 'Regularnie wśród najlepszych w Polsce: medale mistrzostw Polski od 1992 roku i Mistrzostwo Polski w minisiatkówce w 2026.',
                'content' => '<h2>Najnowsze osiągnięcia 2026</h2>'
                    .'<ul>'
                    .'<li>Jokerowcy Wojciech Łobaczewski i Karol Leppert ze złotymi medalami Mistrzostw Europy U-18.</li>'
                    .'<li>Wygrane Mistrzostwa Polski w kategorii U-13 w Gliwicach (minisiatkówka, kat. czwórki).</li>'
                    .'<li>Mistrzostwa Polski Juniorów oraz Juniorów Młodszych w Mrzeżynie.</li>'
                    .'<li>4. miejsce w Polsce w Rankingu Systemu Sportu Młodzieżowego 2026 (piłka siatkowa mężczyzn).</li>'
                    .'</ul>'
                    .'<h2>Młodzieżowe Mistrzostwa Polski (1992-2026)</h2>'
                    .'<h3>Złote medale: 2</h3>'
                    .'<ul><li>2007 Drawsko Pomorskie, młodzik</li><li>2009 Radom, junior</li></ul>'
                    .'<h3>Srebrne medale: 6</h3>'
                    .'<ul><li>1993 Bielsko-Biała, junior młodszy</li><li>2001 Nysa, junior młodszy</li><li>2002 Piła, junior</li><li>2003 Gubin, junior</li><li>2008 Kędzierzyn-Koźle, junior młodszy</li><li>2010 Piła, junior</li></ul>'
                    .'<h3>Brązowe medale: 2</h3>'
                    .'<ul><li>1998 Piła, junior</li><li>2011 Olsztyn, junior</li></ul>'
                    .'<p><strong>Mistrz Polski 2026</strong>: Gliwice, minisiatkówka, kategoria czwórki.</p>'
                    .'<h2>Mistrzostwa Wielkopolski (2012-2026)</h2>'
                    .'<ul><li>13 złotych medali</li><li>14 srebrnych medali</li><li>8 brązowych medali</li></ul>'
                    .'<h2>Minisiatkówka: Mistrzostwa Wielkopolski (2023-2026)</h2>'
                    .'<ul><li>4 złote medale</li><li>4 srebrne medale</li><li>2 brązowe medale</li></ul>'
                    .'<h2>Powołania</h2>'
                    .'<p>Nasi zawodnicy są powoływani do kadr regionalnych, kadr wojewódzkich i młodzieżowych reprezentacji Polski. Organizujemy i uczestniczymy w turniejach w całej Polsce.</p>',
            ],
            [
                'title' => 'Współpraca i sponsoring',
                'slug' => 'wspolpraca',
                'lead' => 'Razem budujemy przyszłość siatkówki i rozwijamy lokalny sport.',
                'content' => '<p>Od wielu lat realizujemy zadania publiczne oraz projekty sportowe we współpracy z instytucjami samorządowymi, państwowymi i ogólnopolskimi organizacjami sportowymi. Dzięki zdobytemu doświadczeniu tworzymy stabilne i długofalowe partnerstwa oparte na wzajemnych korzyściach.</p>'
                    .'<h2>Co oferujemy partnerom</h2>'
                    .'<ul>'
                    .'<li>Promocję marki podczas meczów, turniejów i wydarzeń sportowych.</li>'
                    .'<li>Ekspozycję logo na strojach zawodników, materiałach promocyjnych oraz w mediach społecznościowych.</li>'
                    .'<li>Dotarcie do szerokiego grona odbiorców: zawodników, rodziców, kibiców i lokalnej społeczności.</li>'
                    .'<li>Wspólne akcje marketingowe oraz możliwość organizacji wydarzeń promujących sponsora.</li>'
                    .'<li>Budowanie pozytywnego wizerunku firmy poprzez wspieranie rozwoju dzieci i młodzieży oraz lokalnego sportu.</li>'
                    .'<li>Indywidualnie dopasowane pakiety sponsorskie, uwzględniające potrzeby i cele biznesowe partnera.</li>'
                    .'<li>Regularne raportowanie działań promocyjnych i efektów współpracy.</li>'
                    .'</ul>'
                    .'<h2>Zasięgi</h2>'
                    .'<p>Ponad 6 200 obserwujących na Facebooku, a najważniejsze posty osiągają ponad 30 000 wyświetleń.</p>'
                    .'<h2>Kontakt</h2>'
                    .'<p>Zadzwoń: <a href="tel:+48531064100">+48 531 064 100</a> albo napisz: <a href="mailto:smsjokerpila@gmail.com">smsjokerpila@gmail.com</a>.</p>',
            ],
            [
                'title' => 'Nabór',
                'slug' => 'nabor',
                'lead' => 'Zapraszamy chłopców od 7. roku życia. Szkolimy w każdej kategorii wiekowej, od minisiatkówki po II ligę.',
                'content' => '<h2>Grupy wiekowe</h2>'
                    .'<ul>'
                    .'<li><strong>Minisiatkówka</strong> (7-13 lat): pierwsze kroki w nauce.</li>'
                    .'<li><strong>Młodzik</strong> (14-15 lat): rozwój nabytych umiejętności.</li>'
                    .'<li><strong>Junior młodszy</strong> (16-17 lat): szkolenie ukierunkowane na zdolności.</li>'
                    .'<li><strong>Junior</strong> (18-19 lat): przygotowanie do poziomu senior.</li>'
                    .'</ul>'
                    .'<h2>Jak dołączyć?</h2>'
                    .'<p>Zadzwoń pod numer <a href="tel:+48531064100">+48 531 064 100</a> albo napisz na <a href="mailto:smsjokerpila@gmail.com">smsjokerpila@gmail.com</a>. Podpowiemy, do której grupy przyjść i kiedy są treningi.</p>'
                    .'<p><em>Treść tymczasowa: terminy treningów i szczegóły zapisów do uzupełnienia w panelu (Podstrony).</em></p>',
            ],
            [
                'title' => 'Dla zawodników spoza Piły',
                'slug' => 'spoza-pily',
                'lead' => 'Trenujesz siatkówkę i chcesz rozwijać się w czołowym ośrodku szkolenia młodzieży? Szkoła, treningi i hala są w jednym miejscu w Pile.',
                'content' => '<h2>Szkoła i treningi w jednym miejscu</h2>'
                    .'<p>Od poziomu juniora młodszego szkolimy wspólnie z <a href="https://lo-ms.pila.pl/">Liceum Ogólnokształcącym Mistrzostwa Sportowego w Pile</a>. Lekcje i treningi są ułożone tak, żeby dało się pogodzić naukę z grą na wysokim poziomie.</p>'
                    .'<ul>'
                    .'<li><strong>Adres:</strong> ul. Wincentego Pola 11, 64-920 Piła</li>'
                    .'<li><strong>Telefon szkoły:</strong> <a href="tel:+48673490093">+48 67 349 00 93</a></li>'
                    .'<li><strong>Telefon klubu:</strong> <a href="tel:+48531064100">+48 531 064 100</a></li>'
                    .'</ul>'
                    .'<h2>Treningi</h2>'
                    .'<p>Każda grupa ma stały plan zajęć w tygodniu. Zobacz <a href="/training-schedule">plan treningów</a> i wybierz grupę z rocznika.</p>'
                    .'<h2>Zakwaterowanie i dojazd</h2>'
                    .'<p><em>Do uzupełnienia w panelu (Podstrony): informacje o internacie lub zakwaterowaniu, kosztach i dojeździe do Piły.</em></p>'
                    .'<h2>Jak zacząć?</h2>'
                    .'<p>Zadzwoń albo napisz: umówimy trening próbny i opowiemy o szkole. Zobacz też, kto już przeszedł tę drogę: <a href="/alumni">nasi wychowankowie</a>.</p>',
            ],
            [
                'title' => 'Dla rodziców',
                'slug' => 'dla-rodzicow',
                'lead' => 'Najważniejsze informacje dla rodziców młodych siatkarzy.',
                'content' => '<p><em>Treść tymczasowa do uzupełnienia w panelu (Podstrony).</em></p>'
                    .'<h2>Zapisy</h2><p>Skontaktuj się z nami telefonicznie lub mailowo, a podpowiemy, do której grupy przyjść na pierwszy trening.</p>'
                    .'<h2>Składki i badania</h2><p>Wysokość składki, zasady badań lekarskich i potrzebne dokumenty opiszemy tutaj wkrótce. Formularze znajdziesz w zakładce Dokumenty.</p>'
                    .'<h2>Wyjazdy</h2><p>W sezonie jeździmy na dziesiątki meczów i turniejów. Informacje o zbiórkach przekazują trenerzy drużyn.</p>',
            ],
            [
                'title' => 'Dla kibiców',
                'slug' => 'dla-kibicow',
                'lead' => 'Mecze II ligi i rozgrywek młodzieżowych w Pile: przyjdź i dopinguj Jokera.',
                'content' => '<p><em>Treść tymczasowa do uzupełnienia w panelu (Podstrony).</em></p>'
                    .'<h2>Terminarz</h2><p>Godziny i miejsca meczów wszystkich drużyn znajdziesz w zakładce Mecze. Każdy mecz możesz dodać do swojego kalendarza.</p>'
                    .'<h2>Bądź z nami w sieci</h2><p>Relacje, zdjęcia i transmisje publikujemy na Facebooku, Instagramie, TikToku i YouTube: szukaj nas pod nazwą Joker Piła.</p>',
            ],
            [
                'title' => 'FAQ - najczęstsze pytania',
                'slug' => 'faq',
                'lead' => 'Odpowiedzi na pytania, które słyszymy najczęściej.',
                'content' => '<p><em>Treść tymczasowa do uzupełnienia w panelu (Podstrony).</em></p>'
                    .'<h3>Czy dziecko musi umieć grać w siatkówkę?</h3><p>Nie. W minisiatkówce uczymy podstaw od zera.</p>'
                    .'<h3>Od ilu lat można zacząć?</h3><p>Minisiatkówkę prowadzimy dla chłopców w wieku 7-13 lat.</p>'
                    .'<h3>Jak często są treningi?</h3><p>W zależności od grupy od 5 do 7 zajęć w tygodniu.</p>'
                    .'<h3>Jak zostać partnerem klubu?</h3><p>Zobacz stronę Współpraca i sponsoring albo zadzwoń: +48 531 064 100.</p>',
            ],
            [
                'title' => 'Polityka prywatności',
                'slug' => 'polityka-prywatnosci',
                'lead' => 'Jak klub przetwarza dane osób korzystających z serwisu i kont użytkowników.',
                'content' => '<p><em>Wzór do sprawdzenia przez zarząd klubu (najlepiej z prawnikiem lub inspektorem ochrony danych) i uzupełnienia w panelu (Podstrony).</em></p>'
                    .'<h2>Administrator danych</h2><p>Administratorem danych jest UKS SMS Joker Piła, ul. Wincentego Pola 11, 64-920 Piła, tel. +48 531 064 100. W sprawach danych osobowych napisz przez <a href="/contact">formularz kontaktowy</a>.</p>'
                    .'<h2>Jakie dane i po co</h2><ul>'
                    .'<li><strong>Konto w serwisie</strong>: imię i nazwisko, e-mail, hasło (zaszyfrowane) i dane, które sam podasz w profilu. Podstawa: Twoja zgoda (art. 6 ust. 1 lit. a RODO). Zgoda jest warunkiem założenia konta.</li>'
                    .'<li><strong>Profile zawodników</strong>: imię i nazwisko, drużyna, pozycja, zdjęcie i statystyki. Podstawa: zgoda zawodnika albo jego rodzica (opiekuna) oraz uzasadniony interes klubu w informowaniu o działalności sportowej.</li>'
                    .'<li><strong>Oznaczenia na zdjęciach</strong>: tylko za zgodą na wizerunek, którą można wycofać w „Mój profil”.</li>'
                    .'<li><strong>Formularz kontaktowy</strong>: dane z wiadomości, aby na nią odpowiedzieć.</li>'
                    .'<li><strong>Statystyki odwiedzin</strong>: bez plików cookie śledzących; adresu IP nie zapisujemy wprost, tylko jako jednodniowy skrót (hash).</li><li><strong>Pliki cookie</strong>: tylko techniczne, potrzebne do logowania i działania formularzy.</li>'
                    .'</ul>'
                    .'<h2>Twoje prawa</h2><p>Masz prawo dostępu do danych, ich poprawienia, usunięcia, ograniczenia przetwarzania, przeniesienia oraz cofnięcia zgody w dowolnym momencie (nie wpływa to na zgodność z prawem wcześniejszego przetwarzania). Większość z nich zrobisz sam w „Mój profil”: pobierzesz swoje dane, wyłączysz konto albo cofniesz zgodę i usuniesz konto. Masz też prawo wnieść skargę do Prezesa Urzędu Ochrony Danych Osobowych.</p>'
                    .'<h2>Co się dzieje po cofnięciu zgody</h2><p>Cofnięcie zgody oznacza usunięcie konta: kasujemy imię i nazwisko, e-mail, hasło i dane profilu oraz odłączamy konto od zawodników i drużyn. Komentarze zostają podpisane „Konto usunięte”. Jeśli chcesz tylko na jakiś czas przestać korzystać z konta, możesz je wyłączyć i później włączyć bez ponownej aktywacji.</p>'
                    .'<h2>Dzieci</h2><p>Konto osoby poniżej 16 lat może powstać tylko za zgodą rodzica lub opiekuna. Dane dzieci-zawodników prowadzą rodzice przez swoje konta.</p>'
                    .'<h2>Jak długo</h2><p>Dane konta przechowujemy do czasu cofnięcia zgody lub usunięcia konta. Dane z formularza kontaktowego przez czas potrzebny na odpowiedź.</p>'
                    .'<h2>Odbiorcy</h2><p>Dane przechowuje firma hostingowa obsługująca serwis klubu. Nie sprzedajemy danych i nie przekazujemy ich poza Europejski Obszar Gospodarczy.</p>',
            ],
        ];
    }
}
