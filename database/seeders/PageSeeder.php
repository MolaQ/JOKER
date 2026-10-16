<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Podstrony podlinkowane w górnym pasku i menu (edytowalne w Panel → Podstrony).
     */
    public function run(): void
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
