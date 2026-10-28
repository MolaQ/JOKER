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
            'icon' => 'cog',
            'fields' => [
                'club_name' => ['type' => 'text', 'label' => 'Nazwa klubu', 'default' => 'Joker Piła'],
                'club_tagline' => ['type' => 'text', 'label' => 'Podpis pod nazwą', 'default' => 'Klub siatkówki od 1992 roku'],
                'logo' => ['type' => 'image', 'crop' => ['aspect' => '1/1', 'format' => 'png', 'width' => 512], 'label' => 'Herb / logo', 'help' => 'Najlepiej kwadratowy PNG z przezroczystym tłem. Bez wgranego pliku używany jest herb klubu. Herb jest też domyślnym logo wszystkich drużyn Jokera.', 'default' => null, 'preview' => 'img/logo-256.webp'],
                'site_title' => ['type' => 'text', 'label' => 'Tytuł strony (w karcie przeglądarki i Google)', 'default' => 'Joker Piła - Klub Siatkówki'],
                'site_description' => ['type' => 'textarea', 'label' => 'Opis strony dla Google', 'default' => 'UKS SMS Joker Piła - klub siatkarski z Piły, jeden z czołowych ośrodków szkolenia siatkówki młodzieżowej w Polsce. Drużyny od minisiatkówki po II ligę, terminarz, wyniki i aktualności.'],
            ],
        ],

        'seo' => [
            'label' => 'Wyszukiwarki (SEO)',
            'icon' => 'chart',
            'fields' => [
                'seo_founding_year' => ['type' => 'text', 'label' => 'Rok założenia klubu', 'default' => '1992'],
                'seo_google_verification' => ['type' => 'text', 'label' => 'Google Search Console: kod weryfikacji', 'help' => 'Z Search Console wybierz weryfikację „Tag HTML” i wklej tylko wartość content, np. AbC123...', 'default' => ''],
                'seo_bing_verification' => ['type' => 'text', 'label' => 'Bing Webmaster Tools: kod weryfikacji', 'default' => ''],
            ],
        ],

        'kolory' => [
            'label' => 'Kolory',
            'icon' => 'swatch',
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
            'icon' => 'calendar',
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
            'icon' => 'bell',
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

        'push' => [
            'label' => 'Powiadomienia na telefon',
            'icon' => 'phone',
            'fields' => [
                'push_enabled' => ['type' => 'toggle', 'label' => 'Powiadomienia push włączone', 'help' => 'Kibic po zgodzie dostaje na telefonie lub komputerze wyskakujące powiadomienia, jak z innych serwisów. Na iPhonie działa po dodaniu strony do ekranu początkowego.', 'default' => true],
                'push_prompt' => ['type' => 'toggle', 'label' => 'Zaproszenie do włączenia powiadomień', 'help' => 'Mała karta na dole strony przy drugiej wizycie. Kibic może też włączyć powiadomienia ikoną dzwonka w menu.', 'default' => true],
                'push_prompt_text' => ['type' => 'text', 'label' => 'Tekst zaproszenia', 'default' => 'Chcesz wiedzieć od razu o wynikach meczów, nowych aktualnościach i galeriach?'],
                'push_articles' => ['type' => 'toggle', 'label' => 'Powiadamiaj o nowych aktualnościach', 'default' => true],
                'push_results' => ['type' => 'toggle', 'label' => 'Powiadamiaj o wynikach meczów', 'default' => true],
                'push_galleries' => ['type' => 'toggle', 'label' => 'Powiadamiaj o nowych galeriach', 'default' => true],
            ],
        ],

        'wsparcie' => [
            'label' => 'Wspieraj klub',
            'icon' => 'heart',
            'fields' => [
                'support_intro' => ['type' => 'textarea', 'label' => 'Tekst na górze strony „Wspieraj klub”', 'default' => 'Dzięki wsparciu rodziców, kibiców i firm ponad 100 młodych siatkarzy może trenować, jeździć na turnieje i rozwijać pasję.'],
                'support_krs' => ['type' => 'text', 'label' => 'Numer KRS do przekazania 1,5% podatku', 'help' => 'Puste = sekcja 1,5% jest ukryta. Wpisz tylko, jeśli klub ma status organizacji pożytku publicznego.', 'default' => ''],
                'support_tax_goal' => ['type' => 'text', 'label' => 'Cel szczegółowy w PIT (opcjonalnie)', 'default' => ''],
                'support_account' => ['type' => 'text', 'label' => 'Numer konta do darowizn (puste = ukryty)', 'default' => ''],
                'support_shop_note' => ['type' => 'textarea', 'label' => 'Gadżety: jak zamówić', 'default' => 'Wybierz gadżet i kliknij „Zamów”. Odpiszemy z informacją o płatności i odbiorze (w hali albo na treningu).'],
            ],
        ],

        'turnieje' => [
            'label' => 'Turnieje: drabinka',
            'icon' => 'trophy',
            'fields' => [
                'tm_bar_bg' => ['type' => 'color', 'label' => 'Belka meczu: tło', 'default' => '#0f2d6b'],
                'tm_bar_text' => ['type' => 'color', 'label' => 'Belka meczu: tekst', 'default' => '#ffffff'],
                'tm_number_bg' => ['type' => 'color', 'label' => 'Numer meczu: tło', 'default' => '#facc15'],
                'tm_number_text' => ['type' => 'color', 'label' => 'Numer meczu: tekst', 'default' => '#0f172a'],
            ],
        ],

        'zawodnicy' => [
            'label' => 'Profile zawodników',
            'icon' => 'user',
            'fields' => [
                'player_social_platforms' => ['type' => 'platforms', 'label' => 'Media społecznościowe w profilu zawodnika', 'help' => 'Te serwisy pojawią się w formularzu zawodnika (panel i propozycje od rodziców) i jako ikony w jego profilu. Ikonę wybierasz z listy.', 'default' => [
                    ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'instagram'],
                    ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'facebook'],
                    ['key' => 'tiktok', 'label' => 'TikTok', 'icon' => 'tiktok'],
                    ['key' => 'youtube', 'label' => 'YouTube', 'icon' => 'youtube'],
                    ['key' => 'x', 'label' => 'X (Twitter)', 'icon' => 'x'],
                ]],
            ],
        ],

        'pasek' => [
            'label' => 'Górny pasek',
            'icon' => 'window',
            'fields' => [
                'topbar_enabled' => ['type' => 'toggle', 'label' => 'Pokazuj górny pasek', 'default' => true],
                'topbar_links_left' => ['type' => 'links', 'label' => 'Linki po lewej', 'default' => [
                    ['label' => 'Dla rodziców', 'url' => '/strona/dla-rodzicow'],
                    ['label' => 'Dla kibiców', 'url' => '/strona/dla-kibicow'],
                    ['label' => 'Nabór', 'url' => '/strona/nabor'],
                    ['label' => 'Galerie', 'url' => '/galerie'],
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
            'icon' => 'menu',
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
                'nav_button_url' => ['type' => 'url', 'label' => 'Wyróżniony przycisk: adres', 'default' => '/rejestracja'],
            ],
        ],

        'glowna' => [
            'label' => 'Strona główna',
            'icon' => 'home',
            'fields' => [
                'hero_title' => ['type' => 'text', 'label' => 'Duży napis (gdy nie ma slajdów)', 'default' => 'Gramy razem. Wygrywamy razem.'],
                'hero_subtitle' => ['type' => 'textarea', 'label' => 'Tekst pod dużym napisem', 'default' => 'Każdego dnia około 100 młodych ludzi z Piły i regionu rozwija u nas swoje siatkarskie pasje. Od minisiatkówki po II ligę.'],
                'hero_image' => ['type' => 'image', 'crop' => ['aspect' => '16/9', 'width' => 1920], 'label' => 'Zdjęcie w tle (gdy nie ma slajdów)', 'default' => null],
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
                'home_lineup_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Skład dnia (losowany codziennie, więcej serc = większa szansa)', 'default' => true],
                'home_news_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Aktualności', 'default' => true],
                'home_news_eyebrow' => ['type' => 'text', 'label' => 'Aktualności: mały napis', 'default' => 'Aktualności'],
                'home_news_title' => ['type' => 'text', 'label' => 'Aktualności: tytuł', 'default' => 'Z życia klubu'],
                'home_sponsors_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Sponsorzy', 'default' => true],
                'home_sponsors_title' => ['type' => 'text', 'label' => 'Sponsorzy: napis', 'default' => 'Wspierają nas'],
                'home_alumni_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Wychowankowie (Od Jokera do PlusLigi)', 'default' => true],
                'home_join_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Zaproszenie na trening', 'default' => true],
                'home_join_eyebrow' => ['type' => 'text', 'label' => 'Zaproszenie: mały napis', 'default' => 'Nabór trwa cały rok'],
                'home_join_title' => ['type' => 'text', 'label' => 'Zaproszenie: tytuł', 'default' => 'Dołącz do Jokera'],
                'home_join_text' => ['type' => 'textarea', 'label' => 'Zaproszenie: tekst', 'default' => 'Zapraszamy chłopców od 7. roku życia. Szkolimy w każdej kategorii wiekowej: od minisiatkówki po seniorską II ligę.'],
                'home_join_button_label' => ['type' => 'text', 'label' => 'Zaproszenie: przycisk', 'default' => 'Zobacz nabór'],
                'home_join_button_url' => ['type' => 'url', 'label' => 'Zaproszenie: adres przycisku', 'default' => '/strona/nabor'],
                'home_join_image' => ['type' => 'image', 'crop' => ['aspect' => '16/9', 'width' => 1920], 'label' => 'Zaproszenie: zdjęcie w tle', 'default' => null],
            ],
        ],

        'kontakt' => [
            'label' => 'Kontakt i stopka',
            'icon' => 'envelope',
            'fields' => [
                'contact_email' => ['type' => 'text', 'label' => 'E-mail', 'default' => 'smsjokerpila@gmail.com'],
                'contact_phone' => ['type' => 'text', 'label' => 'Telefon klubu', 'default' => '+48 531 064 100'],
                'contact_address' => ['type' => 'text', 'label' => 'Adres', 'help' => 'Ulica, kod i miasto, np. ul. Wincentego Pola 11, 64-920 Piła. Na stronie Kontakt pod adresem jest link do mapy.', 'default' => 'ul. Wincentego Pola 11, 64-920 Piła'],
                'contact_school_name' => ['type' => 'text', 'label' => 'Szkoła (puste = ukryta)', 'default' => 'Liceum Ogólnokształcące Mistrzostwa Sportowego w Pile'],
                'contact_school_phone' => ['type' => 'text', 'label' => 'Telefon szkoły', 'default' => '+48 67 349 00 93'],
                'contact_school_url' => ['type' => 'url', 'label' => 'Strona szkoły', 'default' => 'https://lo-ms.pila.pl/'],
                'contact_hours' => ['type' => 'textarea', 'label' => 'Godziny otwarcia biura', 'help' => 'Każda linia wyświetli się osobno.', 'default' => ''],
                'contact_intro' => ['type' => 'textarea', 'label' => 'Tekst na stronie Kontakt (pod tytułem)', 'default' => 'Masz pytanie o treningi, nabór, mecze albo współpracę? Zadzwoń lub napisz.'],
                'footer_text' => ['type' => 'text', 'label' => 'Hasło w stopce', 'default' => 'Szkolimy siatkarzy od 1992 roku. Wychowujemy, szkolimy, rozwijamy pasję.'],
                'footer_button_label' => ['type' => 'text', 'label' => 'Przycisk w stopce: tekst', 'default' => 'Dołącz do Jokera'],
                'footer_button_url' => ['type' => 'url', 'label' => 'Przycisk w stopce: adres', 'default' => '/strona/nabor'],
                'footer_links_title' => ['type' => 'text', 'label' => 'Stopka: tytuł kolumny z linkami', 'default' => 'Klub'],
                'footer_links' => ['type' => 'links', 'label' => 'Stopka: linki', 'help' => 'Puste = te same linki co w menu i górnym pasku.', 'default' => [
                    ['label' => 'O klubie', 'url' => '/strona/o-klubie'],
                    ['label' => 'System szkolenia', 'url' => '/strona/system-szkolenia'],
                    ['label' => 'Osiągnięcia', 'url' => '/strona/osiagniecia'],
                    ['label' => 'Galerie zdjęć', 'url' => '/galerie'],
                    ['label' => 'Wychowankowie', 'url' => '/wychowankowie'],
                    ['label' => 'Nabór', 'url' => '/strona/nabor'],
                    ['label' => 'Plan treningów', 'url' => '/plan-treningow'],
                    ['label' => 'Dla zawodników spoza Piły', 'url' => '/strona/spoza-pily'],
                    ['label' => 'Wspieraj klub', 'url' => '/wspieraj-klub'],
                    ['label' => 'Współpraca i sponsoring', 'url' => '/strona/wspolpraca'],
                    ['label' => 'Dokumenty', 'url' => '/dokumenty'],
                    ['label' => 'Kontakt', 'url' => '/kontakt'],
                ]],
                'footer_note' => ['type' => 'text', 'label' => 'Stopka: dopisek na dole (np. nazwa stowarzyszenia, NIP)', 'default' => 'UKS SMS Joker Piła'],
                'footer_bottom_links' => ['type' => 'links', 'label' => 'Stopka: linki w dolnym pasku', 'help' => 'Np. polityka prywatności. Gdy na stronie nie ma już zdjęć z Unsplash, usuń link do nich.', 'default' => [
                    ['label' => 'Polityka prywatności', 'url' => '/strona/polityka-prywatnosci'],
                    ['label' => 'Zdjęcia tematyczne: Unsplash i ich autorzy', 'url' => '/zdjecia'],
                ]],
                'footer_author' => ['type' => 'text', 'label' => 'Stopka: autor strony', 'help' => 'Pokazuje się na dole jako „Projekt i wykonanie: …”. Puste = bez podpisu.', 'default' => ''],
                'footer_author_url' => ['type' => 'url', 'label' => 'Stopka: link do autora strony (opcjonalnie)', 'default' => ''],
                'social_facebook' => ['type' => 'url', 'label' => 'Facebook (adres profilu)', 'default' => 'https://www.facebook.com/UKSSMSJOKERPILA'],
                'social_instagram' => ['type' => 'url', 'label' => 'Instagram', 'default' => 'https://www.instagram.com/joker_pila'],
                'social_youtube' => ['type' => 'url', 'label' => 'YouTube', 'default' => 'https://www.youtube.com/@JokerPila'],
                'social_tiktok' => ['type' => 'url', 'label' => 'TikTok', 'default' => 'https://www.tiktok.com/@joker_pila'],
                'social_x' => ['type' => 'url', 'label' => 'X (Twitter)', 'default' => ''],
            ],
        ],

    ],

];
