<?php

/*
|--------------------------------------------------------------------------
| Ustawienia strony edytowane w panelu (Panel → Wygląd i ustawienia)
|--------------------------------------------------------------------------
|
| Każde pole ma typ, etykietę, podpowiedź i wartość domyślną. Panel buduje
| formularz automatycznie z tej listy, więc nowe ustawienie dodajesz tutaj
| i od razu pojawia się w panelu. Typy: text, textarea, url, color, toggle,
| image, links (lista odnośników: etykieta + adres).
|
*/

return [

    'groups' => [

        'ogolne' => [
            'label' => 'Ogólne',
            'icon' => '⚙️',
            'fields' => [
                'club_name' => ['type' => 'text', 'label' => 'Nazwa klubu', 'default' => 'Joker Piła'],
                'club_tagline' => ['type' => 'text', 'label' => 'Podpis pod nazwą', 'default' => 'Klub siatkówki'],
                'logo' => ['type' => 'image', 'label' => 'Herb / logo', 'help' => 'Najlepiej kwadratowy PNG z przezroczystym tłem.', 'default' => null],
                'site_title' => ['type' => 'text', 'label' => 'Tytuł strony (w karcie przeglądarki i Google)', 'default' => 'Joker Piła - Klub Siatkówki'],
                'site_description' => ['type' => 'textarea', 'label' => 'Opis strony dla Google', 'default' => 'Joker Piła - klub siatkarski z Piły. Drużyny od młodzików po seniorów, terminarz, wyniki i aktualności.'],
            ],
        ],

        'kolory' => [
            'label' => 'Kolory',
            'icon' => '🎨',
            'fields' => [
                'color_accent' => ['type' => 'color', 'label' => 'Kolor główny (przyciski, wyróżnienia)', 'default' => '#45a0e0'],
                'color_dark' => ['type' => 'color', 'label' => 'Kolor ciemnego tła (nagłówek, stopka)', 'default' => '#12141a'],
                'color_dark_alt' => ['type' => 'color', 'label' => 'Drugi ciemny kolor (karty na ciemnym tle)', 'default' => '#22252d'],
                'color_page' => ['type' => 'color', 'label' => 'Tło strony', 'default' => '#fafafa'],
                'color_topbar_bg' => ['type' => 'color', 'label' => 'Górny pasek: tło', 'default' => '#ffd600'],
                'color_topbar_text' => ['type' => 'color', 'label' => 'Górny pasek: tekst', 'default' => '#12141a'],
                'color_nav_button_bg' => ['type' => 'color', 'label' => 'Wyróżniony przycisk w menu: tło', 'default' => '#ffd600'],
                'color_nav_button_text' => ['type' => 'color', 'label' => 'Wyróżniony przycisk w menu: tekst', 'default' => '#12141a'],
                'color_countdown' => ['type' => 'color', 'label' => 'Cyfry zegara odliczającego do meczu', 'default' => '#ffd600'],
                'color_club_navy' => ['type' => 'color', 'label' => 'Granat klubowy (kalendarz: belka miesiąca i mecze u siebie)', 'default' => '#0f2d6b'],
            ],
        ],

        'kalendarz' => [
            'label' => 'Kalendarz meczów',
            'icon' => '📅',
            'fields' => [
                'cal_home_bg' => ['type' => 'color', 'label' => 'Mecz domowy: tło', 'default' => '#0f2d6b'],
                'cal_home_text' => ['type' => 'color', 'label' => 'Mecz domowy: tekst', 'default' => '#2f7fbf'],
                'cal_home_border' => ['type' => 'color', 'label' => 'Mecz domowy: ramka', 'default' => '#2f7fbf'],
                'cal_away_bg' => ['type' => 'color', 'label' => 'Mecz wyjazdowy: tło', 'default' => '#ffffff'],
                'cal_away_text' => ['type' => 'color', 'label' => 'Mecz wyjazdowy: tekst', 'default' => '#2f7fbf'],
                'cal_away_border' => ['type' => 'color', 'label' => 'Mecz wyjazdowy: ramka', 'default' => '#a9cfee'],
                'cal_birthday_icon' => ['type' => 'text', 'label' => 'Urodziny: ikona (emoji)', 'default' => '🎂'],
                'cal_birthday_bg' => ['type' => 'color', 'label' => 'Urodziny: tło', 'default' => '#fdf2f8'],
                'cal_birthday_text' => ['type' => 'color', 'label' => 'Urodziny: tekst', 'default' => '#9d174d'],
            ],
        ],

        'powiadomienia' => [
            'label' => 'Powiadomienia (toasty)',
            'icon' => '🔔',
            'fields' => [
                'toast_success_bg' => ['type' => 'color', 'label' => 'Sukces: tło', 'default' => '#ecfdf5'],
                'toast_success_text' => ['type' => 'color', 'label' => 'Sukces: tekst i ikona', 'default' => '#047857'],
                'toast_success_title' => ['type' => 'text', 'label' => 'Sukces: tytuł', 'default' => 'Gotowe'],
                'toast_error_bg' => ['type' => 'color', 'label' => 'Błąd: tło', 'default' => '#fef2f2'],
                'toast_error_text' => ['type' => 'color', 'label' => 'Błąd: tekst i ikona', 'default' => '#b91c1c'],
                'toast_error_title' => ['type' => 'text', 'label' => 'Błąd: tytuł', 'default' => 'Coś poszło nie tak'],
                'toast_seconds' => ['type' => 'text', 'label' => 'Czas wyświetlania (sekundy)', 'default' => '4'],
                'toast_position' => ['type' => 'select', 'label' => 'Miejsce na ekranie', 'default' => 'top-right', 'options' => [
                    'top-right' => 'Prawy górny róg',
                    'bottom-right' => 'Prawy dolny róg',
                    'top-center' => 'Na górze, na środku',
                    'bottom-center' => 'Na dole, na środku',
                ]],
            ],
        ],

        'pasek' => [
            'label' => 'Górny pasek',
            'icon' => '🟨',
            'fields' => [
                'topbar_enabled' => ['type' => 'toggle', 'label' => 'Pokazuj górny pasek', 'default' => true],
                'topbar_links_left' => ['type' => 'links', 'label' => 'Linki po lewej', 'default' => [
                    ['label' => 'Dla rodziców', 'url' => '/strona/dla-rodzicow'],
                    ['label' => 'Dla kibiców', 'url' => '/strona/dla-kibicow'],
                    ['label' => 'Nabór', 'url' => '/strona/nabor'],
                    ['label' => 'Dokumenty', 'url' => '/dokumenty'],
                ]],
                'topbar_links_right' => ['type' => 'links', 'label' => 'Linki po prawej', 'default' => [
                    ['label' => 'Kontakt', 'url' => '/kontakt'],
                    ['label' => 'FAQ', 'url' => '/strona/faq'],
                ]],
                'topbar_show_search' => ['type' => 'toggle', 'label' => 'Pole wyszukiwania w pasku', 'default' => true],
                'topbar_show_social' => ['type' => 'toggle', 'label' => 'Ikony mediów społecznościowych w pasku', 'default' => true],
            ],
        ],

        'menu' => [
            'label' => 'Menu główne',
            'icon' => '🧭',
            'fields' => [
                'nav_items' => ['type' => 'links', 'label' => 'Pozycje menu', 'help' => 'Kolejność ustawiasz strzałkami.', 'default' => [
                    ['label' => 'Aktualności', 'url' => '/aktualnosci'],
                    ['label' => 'Mecze', 'url' => '/mecze'],
                    ['label' => 'Tabela', 'url' => '/tabele'],
                    ['label' => 'Memoriał', 'url' => '/memorial'],
                    ['label' => 'Drużyny', 'url' => '/druzyny'],
                    ['label' => 'Młodzież', 'url' => '/strona/nabor'],
                    ['label' => 'Klub', 'url' => '/strona/o-klubie'],
                ]],
                'nav_button_enabled' => ['type' => 'toggle', 'label' => 'Wyróżniony przycisk na końcu menu', 'default' => true],
                'nav_button_label' => ['type' => 'text', 'label' => 'Wyróżniony przycisk: tekst', 'default' => 'Dołącz'],
                'nav_button_url' => ['type' => 'url', 'label' => 'Wyróżniony przycisk: adres', 'default' => '/kontakt'],
            ],
        ],

        'glowna' => [
            'label' => 'Strona główna',
            'icon' => '🏠',
            'fields' => [
                'hero_title' => ['type' => 'text', 'label' => 'Duży napis (gdy nie ma slajdów)', 'default' => 'Gramy razem. Wygrywamy razem.'],
                'hero_subtitle' => ['type' => 'textarea', 'label' => 'Tekst pod dużym napisem', 'default' => 'Klub siatkarski z Piły. Od pierwszego odbicia młodzika po mecze seniorów.'],
                'hero_image' => ['type' => 'image', 'label' => 'Zdjęcie w tle (gdy nie ma slajdów)', 'default' => null],
                'hero_button_label' => ['type' => 'text', 'label' => 'Pierwszy przycisk: tekst (gdy nie ma slajdów)', 'default' => 'Poznaj drużyny'],
                'hero_button_url' => ['type' => 'url', 'label' => 'Pierwszy przycisk: adres', 'default' => '/druzyny'],
                'hero_second_button_label' => ['type' => 'text', 'label' => 'Drugi przycisk: tekst (puste = ukryty)', 'default' => 'Terminarz meczów'],
                'hero_second_button_url' => ['type' => 'url', 'label' => 'Drugi przycisk: adres', 'default' => '/mecze'],
                'home_tile_label' => ['type' => 'text', 'label' => 'Kafelek przy najbliższym meczu: tekst', 'default' => 'Dołącz do klubu'],
                'home_tile_url' => ['type' => 'url', 'label' => 'Kafelek przy najbliższym meczu: adres', 'default' => '/kontakt'],
                'home_show_countdown' => ['type' => 'toggle', 'label' => 'Zegar odliczający do meczu', 'default' => true],
                'countdown_target' => ['type' => 'select', 'label' => 'Zegar odlicza do', 'help' => 'Gdy seniorzy nie mają zaplanowanego meczu, zegar pokaże najbliższy mecz innej drużyny.', 'default' => 'seniors', 'options' => [
                    'seniors' => 'Najbliższego meczu seniorów',
                    'any' => 'Najbliższego meczu dowolnej drużyny Jokera',
                ]],
                'home_show_stats' => ['type' => 'toggle', 'label' => 'Liczby klubu (drużyny, zawodnicy)', 'default' => true],
                'home_teams_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Drużyny', 'default' => true],
                'home_teams_eyebrow' => ['type' => 'text', 'label' => 'Drużyny: mały napis', 'default' => 'Nasze drużyny'],
                'home_teams_title' => ['type' => 'text', 'label' => 'Drużyny: tytuł', 'default' => 'Od młodzika do seniora'],
                'home_games_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Terminarz i wyniki', 'default' => true],
                'home_games_eyebrow' => ['type' => 'text', 'label' => 'Terminarz: mały napis', 'default' => 'Mecze'],
                'home_games_title' => ['type' => 'text', 'label' => 'Terminarz: tytuł', 'default' => 'Terminarz i wyniki'],
                'home_news_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Aktualności', 'default' => true],
                'home_news_eyebrow' => ['type' => 'text', 'label' => 'Aktualności: mały napis', 'default' => 'Aktualności'],
                'home_news_title' => ['type' => 'text', 'label' => 'Aktualności: tytuł', 'default' => 'Z życia klubu'],
                'home_sponsors_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Sponsorzy', 'default' => true],
                'home_sponsors_title' => ['type' => 'text', 'label' => 'Sponsorzy: napis', 'default' => 'Wspierają nas'],
                'home_join_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Zaproszenie na trening', 'default' => true],
                'home_join_eyebrow' => ['type' => 'text', 'label' => 'Zaproszenie: mały napis', 'default' => 'Nabór trwa cały rok'],
                'home_join_title' => ['type' => 'text', 'label' => 'Zaproszenie: tytuł', 'default' => 'Pierwszy trening jest za darmo'],
                'home_join_text' => ['type' => 'textarea', 'label' => 'Zaproszenie: tekst', 'default' => 'Zapraszamy dziewczęta i chłopców od 8. roku życia. Sprzęt na start pożyczymy, uśmiech zabierz ze sobą.'],
                'home_join_button_label' => ['type' => 'text', 'label' => 'Zaproszenie: przycisk', 'default' => 'Umów pierwszy trening'],
                'home_join_button_url' => ['type' => 'url', 'label' => 'Zaproszenie: adres przycisku', 'default' => '/kontakt'],
                'home_join_image' => ['type' => 'image', 'label' => 'Zaproszenie: zdjęcie w tle', 'default' => null],
            ],
        ],

        'kontakt' => [
            'label' => 'Kontakt i stopka',
            'icon' => '📇',
            'fields' => [
                'contact_email' => ['type' => 'text', 'label' => 'E-mail', 'default' => 'kontakt@jokerpila.pl'],
                'contact_phone' => ['type' => 'text', 'label' => 'Telefon', 'default' => '+48 123 456 789'],
                'contact_address' => ['type' => 'text', 'label' => 'Adres', 'default' => 'ul. Sportowa 1, 64-920 Piła'],
                'contact_hours' => ['type' => 'textarea', 'label' => 'Godziny otwarcia biura', 'help' => 'Każda linia wyświetli się osobno.', 'default' => "Poniedziałek - piątek: 9:00 - 17:00\nSobota i niedziela: nieczynne"],
                'contact_intro' => ['type' => 'textarea', 'label' => 'Tekst na stronie Kontakt (pod tytułem)', 'default' => 'Masz pytanie o treningi, mecze albo współpracę? Napisz lub zadzwoń, odpowiadamy szybko.'],
                'footer_text' => ['type' => 'text', 'label' => 'Hasło w stopce', 'default' => 'Klub siatkówki. Pasja, charakter, drużyna.'],
                'footer_button_label' => ['type' => 'text', 'label' => 'Przycisk w stopce: tekst', 'default' => 'Zapisz dziecko na trening'],
                'footer_button_url' => ['type' => 'url', 'label' => 'Przycisk w stopce: adres', 'default' => '/kontakt'],
                'social_facebook' => ['type' => 'url', 'label' => 'Facebook (adres profilu)', 'default' => 'https://facebook.com/'],
                'social_instagram' => ['type' => 'url', 'label' => 'Instagram', 'default' => 'https://instagram.com/'],
                'social_youtube' => ['type' => 'url', 'label' => 'YouTube', 'default' => ''],
                'social_tiktok' => ['type' => 'url', 'label' => 'TikTok', 'default' => ''],
                'social_x' => ['type' => 'url', 'label' => 'X (Twitter)', 'default' => ''],
            ],
        ],

    ],

];
