<?php

namespace App\Support;

/**
 * Propozycje opisu zasad gry dla grup minisiatkówki (singielki, dwójki, trójki, czwórki), na podstawie regulaminu
 * Mistrzostw Polski w Minisiatkówce o Puchar Kinder Joy of Moving 2024/2025 i „Przepisów i zasad gry w mini siatkówce” PZPS.
 * Administrator wstawia propozycję jednym kliknięciem i poprawia ją w edytorze.
 */
class MiniRules
{
    public static function has(int $playersOnCourt): bool
    {
        return in_array($playersOnCourt, [1, 2, 3, 4], true);
    }

    public static function template(int $playersOnCourt): string
    {
        $format = [
            1 => [
                'intro' => 'Singielki (jedynki) to pierwszy krok w minisiatkówce: jedno dziecko po każdej stronie siatki na małym boisku. Dziecko uczy się czytać lot piłki, ustawiać się pod nią i przebijać ją na drugą stronę. Wolno lekko przytrzymać piłkę, a podwójne odbicie nie jest błędem.',
                'category' => 'kategoria U-10 w Mistrzostwach Polski w Minisiatkówce o Puchar Kinder Joy of Moving',
                'court' => '8 × 3 m',
                'net' => '1,90 m dla dziewcząt i chłopców',
                'ball' => 'piłka nr 4 (lżejsza i mniejsza od seniorskiej)',
                'team' => '1 zawodnik, bez rezerwowych (nie ma zmian)',
                'serve' => 'Zagrywka zza linii końcowej sposobem dolnym, jednorącz górą albo oburącz górą. Piłka wyrzucona zamiast odbitej to błąd.',
                'extra' => ['Gra toczy się do trzech odbić, ale ten sam zawodnik może odbić piłkę kilka razy z rzędu.', 'Nieznaczne przytrzymanie piłki nie jest błędem; rzucenie piłki już tak.'],
            ],
            2 => [
                'intro' => 'Dwójki to gra dwóch na dwóch. Każde dziecko jest ciągle przy piłce: uczy się odbić sposobem dolnym i górnym, ustawienia i współpracy z partnerem.',
                'category' => 'kategoria U-11 w Mistrzostwach Polski w Minisiatkówce o Puchar Kinder Joy of Moving',
                'court' => '10 × 4,5 m',
                'net' => '2,00 m dla dziewcząt i chłopców',
                'ball' => 'piłka nr 4 (lżejsza i mniejsza od seniorskiej)',
                'team' => '2 zawodników na boisku i do 2 rezerwowych',
                'serve' => 'Zagrywka zza linii końcowej sposobem dolnym, jednorącz górą albo oburącz górą.',
                'extra' => ['Zawodnik zagrywający może atakować i blokować.', 'Nie ma błędu ustawienia: każdy może grać przy siatce i z tyłu boiska.'],
            ],
            3 => [
                'intro' => 'Trójki to gra trzech na trzech na dłuższym boisku. Pojawia się podział na grę przy siatce i w obronie, rozegranie i pierwsze ataki.',
                'category' => 'kategoria U-12 w Mistrzostwach Polski w Minisiatkówce o Puchar Kinder Joy of Moving',
                'court' => '14 × 4,5 m',
                'net' => '2,10 m dla dziewcząt i chłopców',
                'ball' => 'piłka nr 4',
                'team' => '3 zawodników na boisku i do 2 rezerwowych',
                'serve' => 'Zagrywka zza linii końcowej.',
                'extra' => ['Zagrywający jest zawodnikiem drugiej linii: przy przyjęciu zagrywki stoi najbliżej linii końcowej, a koledzy przed nim. Liczy się kolejność ustawienia.', 'Zagrywający nie może blokować ani atakować piłki, która w chwili uderzenia jest całkowicie powyżej górnej taśmy siatki. Zostaje na tej pozycji aż do kolejnej rotacji.', 'Nie wolno zasłaniać zagrywającego: przy zagrywce ręce zawodników mogą być uniesione najwyżej do wysokości głowy.'],
            ],
            4 => [
                'intro' => 'Czwórki to najstarsza kategoria minisiatkówki i ostatni krok przed siatkówką szóstek. Gra przypomina już prawdziwy mecz: przyjęcie, rozegranie, atak i blok.',
                'category' => 'kategoria U-13 w Mistrzostwach Polski w Minisiatkówce o Puchar Kinder Joy of Moving',
                'court' => '14 × 7 m',
                'net' => '2,15 m dla dziewcząt i 2,24 m dla chłopców',
                'ball' => 'piłka nr 5',
                'team' => '4 zawodników na boisku i do 2 rezerwowych',
                'serve' => 'Zagrywka zza linii końcowej.',
                'extra' => ['Zagrywający jest zawodnikiem drugiej linii: przy przyjęciu zagrywki stoi najbliżej linii końcowej, a koledzy przed nim. Liczy się kolejność ustawienia.', 'Zagrywający nie może blokować ani atakować piłki, która w chwili uderzenia jest całkowicie powyżej górnej taśmy siatki. Zostaje na tej pozycji aż do kolejnej rotacji.', 'Nie wolno zasłaniać zagrywającego: przy zagrywce ręce zawodników mogą być uniesione najwyżej do wysokości głowy.'],
            ],
        ][$playersOnCourt] ?? null;

        if ($format === null) {
            return '';
        }

        $extra = collect($format['extra'])->map(fn (string $line) => '<li>'.e($line).'</li>')->implode('');

        return '<p>'.e($format['intro']).' To '.e($format['category']).'.</p>'
            .'<h3>Boisko i sprzęt</h3><ul>'
            .'<li><strong>Boisko:</strong> '.e($format['court']).'</li>'
            .'<li><strong>Wysokość siatki:</strong> '.e($format['net']).'</li>'
            .'<li><strong>Piłka:</strong> '.e($format['ball']).'</li>'
            .'<li><strong>Drużyna:</strong> '.e($format['team']).'</li></ul>'
            .'<h3>Jak liczymy punkty</h3><ul>'
            .'<li>Mecz to 2 sety do 15 punktów, z przewagą 2 punktów.</li>'
            .'<li>Przy stanie 1:1 gra się trzeci set do 11 punktów, też z przewagą 2 punktów; po 6. punkcie zmiana stron.</li>'
            .'<li>Każda drużyna ma w secie jedną przerwę na żądanie (30 sekund).</li>'
            .'<li>W turnieju za wygraną drużyna dostaje 2 punkty, za przegraną 1 punkt.</li></ul>'
            .'<h3>Zagrywka i rotacja</h3><ul>'
            .'<li>'.e($format['serve']).'</li>'
            .($playersOnCourt > 1 ? '<li>Gdy drużyna odbierze zagrywkę rywalom, przechodzi o jedno miejsce zgodnie z ruchem wskazówek zegara. Kolejność zagrywających ustala ustawienie na początku seta i pilnuje jej sędzia.</li>' : '')
            .$extra.'</ul>'
            .'<h3>Gra</h3><ul>'
            .'<li>Piłkę można odbić każdą częścią ciała. Na przebicie piłki na drugą stronę są 3 odbicia (plus odbicie blokiem).</li>'
            .($playersOnCourt > 1 ? '<li>Ten sam zawodnik nie może odbić piłki dwa razy z rzędu.</li>'
                .'<li>Każdy rezerwowy może wejść raz w secie, a wrócić można tylko za tego samego zawodnika (dwóch rezerwowych to dwie zmiany i dwie zmiany powrotne w secie).</li>' : '')
            .'</ul>'
            .'<h3>Dla rodziców</h3><ul>'
            .'<li>Na turniej dzieci zabierają strój klubowy (koszulka z numerem, spodenki), czyste obuwie sportowe na halę i wodę.</li>'
            .'<li>W minisiatkówce najważniejsza jest radość z gry i fair play. Kibicujemy wszystkim dzieciom, także drużynom rywali.</li></ul>'
            .'<p><em>Na podstawie regulaminu Mistrzostw Polski w Minisiatkówce o Puchar Kinder Joy of Moving (sezon 2024/2025) i przepisów minisiatkówki PZPS. Roczniki w kategoriach zmieniają się co sezon, a szczegóły turnieju zawsze podaje organizator.</em></p>';
    }
}
