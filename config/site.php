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
                'club_short_name' => ['type' => 'text', 'label' => 'Krótka nazwa klubu', 'help' => 'Np. w kalendarzu meczów i turniejach: „Joker vs Kalisz”.', 'default' => 'Joker'],
                'club_name_genitive' => ['type' => 'text', 'label' => 'Nazwa klubu: kogo? czego?', 'help' => 'Odmiana do zdań typu „wychowankowie Jokera”, „jak wesprzeć Jokera”.', 'default' => 'Jokera'],
                'club_name_locative' => ['type' => 'text', 'label' => 'Nazwa klubu: w kim? w czym?', 'help' => 'Do zdań typu „lata w Jokerze”.', 'default' => 'Jokerze'],
                'club_legal_name' => ['type' => 'text', 'label' => 'Pełna nazwa (stowarzyszenie)', 'help' => 'W zgodach na przetwarzanie danych (RODO).', 'default' => 'UKS SMS Joker Piła'],
                'trash_days' => ['type' => 'select', 'label' => 'Kosz: ile dni trzymać usunięte rekordy', 'help' => 'Usunięte w panelu konto, zawodnika, aktualność, mecz, turniej, drużynę, galerię albo podstronę można w tym czasie przywrócić (System → Kosz). Potem znikają na stałe.', 'default' => '30', 'options' => ['7' => '7 dni', '14' => '14 dni', '30' => '30 dni', '60' => '60 dni', '90' => '90 dni']],
                'club_tagline' => ['type' => 'text', 'label' => 'Podpis pod nazwą', 'default' => 'Klub siatkówki od 1992 roku'],
                'logo' => ['type' => 'image', 'crop' => ['aspect' => '1/1', 'format' => 'png', 'width' => 512], 'label' => 'Herb / logo', 'help' => 'Najlepiej kwadratowy PNG z przezroczystym tłem. Bez wgranego pliku używany jest herb klubu. Herb jest też domyślnym logo wszystkich drużyn klubu.', 'default' => null, 'preview' => 'img/logo-256.webp'],
                'site_title' => ['type' => 'text', 'label' => 'Tytuł strony (w karcie przeglądarki i Google)', 'default' => 'Joker Piła - Klub Siatkówki'],
                'site_description' => ['type' => 'textarea', 'label' => 'Opis strony dla Google', 'default' => 'UKS SMS Joker Piła - klub siatkarski z Piły, jeden z czołowych ośrodków szkolenia siatkówki młodzieżowej w Polsce. Drużyny od minisiatkówki po II ligę, terminarz, wyniki i aktualności.'],
            ],
        ],

        'rozgrywki' => [
            'label' => 'Nasze drużyny w rozgrywkach',
            'icon' => 'trophy',
            'fields' => [
                'competition_club_name' => ['type' => 'text', 'label' => 'Nazwa naszych drużyn w rozgrywkach i turniejach', 'help' => 'Podpis domyślny, np. „LOMS Joker Piła”. W konkretnych rozgrywkach można go zmienić.', 'default' => 'LOMS Joker Piła'],
                'competition_other_names' => ['type' => 'textarea', 'label' => 'Inne nazwy, pod którymi grają nasi zawodnicy', 'help' => 'Po jednej w linii: fragment nazwy z terminarza = podpis. Np. „ASBL = KS ASBL Piła”. Używane przy zakładaniu nowych rozgrywek.', 'default' => 'ASBL = KS ASBL Piła'],
                'competition_name_keys' => ['type' => 'textarea', 'label' => 'Po czym rozpoznać nasze drużyny w terminarzach', 'help' => 'Fragmenty nazw, po jednym w linii (np. joker). Mecz dwóch takich drużyn to derby, a nieprzypisaną drużynę z takim fragmentem import terminarza zgłosi do sprawdzenia.', 'default' => "joker\nasbl pila"],
            ],
        ],

        'moduly' => [
            'label' => 'Moduły',
            'icon' => 'window',
            'fields' => [
                'logos_page' => ['type' => 'toggle', 'label' => 'Strona z logotypami klubów (/logotypy)', 'help' => 'Logotypy naszego klubu i wszystkich rywali do pobrania pojedynczo albo w paczce ZIP, np. do grafik transmisji. Strona nie trafia do Google i nie ma jej w menu; podaj adres osobom, które jej potrzebują.', 'default' => true],
                'logos_login' => ['type' => 'toggle', 'label' => 'Logotypy tylko dla zalogowanych', 'help' => 'Wyłączone: każdy, kto zna adres, może pobrać logotypy.', 'default' => false],
                'module_derby' => ['type' => 'toggle', 'label' => 'Derby', 'help' => 'Mecz dwóch naszych drużyn (np. Joker - ASBL) pokazujemy jako derby z serduszkiem, a nie jako wygraną albo porażkę.', 'default' => true],
                'derby_short' => ['type' => 'text', 'label' => 'Derby: krótki napis przy wyniku', 'default' => 'Pilska siatkówka'],
                'derby_winner' => ['type' => 'text', 'label' => 'Derby: napis o zwycięzcy', 'help' => 'W wynikach i w powiadomieniu na telefon.', 'default' => 'Wygrała pilska siatkówka'],
                'featured_tournament_name' => ['type' => 'text', 'label' => 'Cykliczny turniej klubu', 'help' => 'Nazwa turnieju organizowanego co roku, np. „Memoriał im. Włodzimierza Winklera”. Adres /memorial otwiera jego najnowszą edycję. Puste pole wyłącza moduł.', 'default' => 'Memoriał im. Włodzimierza Winklera'],
                'featured_tournament_eyebrow' => ['type' => 'text', 'label' => 'Cykliczny turniej: mały napis przed ogłoszeniem edycji', 'default' => 'Turniej młodzieżowy w Pile'],
                'module_alumni' => ['type' => 'toggle', 'label' => 'Wychowankowie', 'help' => 'Strona /alumni i sekcja na stronie głównej.', 'default' => true],
                'alumni_eyebrow' => ['type' => 'text', 'label' => 'Wychowankowie: mały napis', 'help' => 'Puste pole: „Od [nazwa klubu] do PlusLigi”.', 'default' => ''],
                'alumni_intro' => ['type' => 'textarea', 'label' => 'Wychowankowie: wstęp', 'default' => 'Zaczynali w naszych grupach młodzieżowych, dziś grają na najwyższym poziomie. Każdy z nich przeszedł przez treningi w Pile.'],
                'trainings_extra_label' => ['type' => 'text', 'label' => 'Plan treningów: dodatkowy przycisk', 'help' => 'Np. „Jesteś spoza Piły?”. Puste pole ukrywa przycisk.', 'default' => 'Jesteś spoza Piły?'],
                'trainings_extra_url' => ['type' => 'url', 'label' => 'Plan treningów: adres dodatkowego przycisku', 'default' => '/page/spoza-pily'],
                'venue_court1' => ['type' => 'text', 'label' => 'Sala: nazwa prawej części', 'help' => 'Sala jest podzielona w poprzek na trzy części. Nazwy pokazujemy w planie treningów i przy meczach turnieju.', 'default' => 'Sektor I'],
                'venue_court2' => ['type' => 'text', 'label' => 'Sala: nazwa środkowej części', 'default' => 'Sektor II'],
                'venue_court3' => ['type' => 'text', 'label' => 'Sala: nazwa lewej części', 'default' => 'Sektor III'],
                'venue_main' => ['type' => 'text', 'label' => 'Sala: nazwa całej sali', 'default' => 'Główne boisko'],
                'venue_gym' => ['type' => 'text', 'label' => 'Nazwa siłowni', 'default' => 'Siłownia'],
            ],
        ],

        'minisiatkowka' => [
            'label' => 'Minisiatkówka',
            'icon' => 'star',
            'fields' => [
                'module_mini' => ['type' => 'toggle', 'label' => 'Minisiatkówka włączona', 'help' => 'Strona /minisiatkowka, strony grup, kafle na stronie głównej i panel „Minisiatkówka”. Wyłączenie ukrywa wszystko, dane zostają.', 'default' => true],
                'mini_title' => ['type' => 'text', 'label' => 'Tytuł', 'default' => 'Minisiatkówka'],
                'mini_eyebrow' => ['type' => 'text', 'label' => 'Mały napis nad tytułem', 'default' => 'Pierwsze kroki'],
                'mini_intro' => ['type' => 'textarea', 'label' => 'Wstęp na stronie /minisiatkowka (puste = bez wstępu)', 'default' => ''],
                'mini_show_children' => ['type' => 'toggle', 'label' => 'Pokazuj dzieci na stronie grupy', 'default' => true],
                'mini_child_names' => ['type' => 'select', 'label' => 'Podpis dziecka', 'default' => 'full', 'options' => [
                    'full' => 'Imię i nazwisko',
                    'initial' => 'Imię i inicjał nazwiska',
                    'first' => 'Tylko imię',
                ]],
                'mini_show_birth_year' => ['type' => 'toggle', 'label' => 'Pokazuj rocznik dziecka', 'default' => true],
                'mini_show_photos' => ['type' => 'toggle', 'label' => 'Pokazuj zdjęcia dzieci (tylko ze zgodą rodzica)', 'help' => 'Wyłączone: wszystkie dzieci mają sylwetkę.', 'default' => true],
                'mini_show_trips' => ['type' => 'toggle', 'label' => 'Pokazuj najbliższe wyjazdy', 'default' => true],
                'mini_show_lineups' => ['type' => 'toggle', 'label' => 'Pokazuj składy drużyn na wyjazdach', 'default' => true],
                'mini_show_parent_info' => ['type' => 'toggle', 'label' => 'Szczegóły wyjazdu dla zalogowanych rodziców', 'help' => 'Zbiórka, transport, koszt. Widzą je tylko rodzice i osoby z panelu.', 'default' => true],
                'mini_show_results' => ['type' => 'toggle', 'label' => 'Pokazuj wyniki', 'default' => true],
                'mini_show_history' => ['type' => 'toggle', 'label' => 'Pokazuj historię i medale', 'default' => true],
                'mini_show_rules' => ['type' => 'toggle', 'label' => 'Pokazuj zasady gry', 'default' => true],
                'mini_tile_dots' => ['type' => 'toggle', 'label' => 'Kafel: ikonki zawodników (liczba graczy na boisku)', 'default' => true],
                'mini_tile_count' => ['type' => 'toggle', 'label' => 'Kafel: liczba dzieci', 'default' => true],
                'mini_tile_medal' => ['type' => 'toggle', 'label' => 'Kafel: złoty medal', 'default' => true],
                'mini_tile_live' => ['type' => 'toggle', 'label' => 'Kafel: najbliższy wyjazd albo ostatni wynik', 'default' => true],
                'mini_in_calendar' => ['type' => 'toggle', 'label' => 'Turnieje minisiatkówki w kalendarzu meczów', 'default' => true],
                'mini_team_prefix' => ['type' => 'text', 'label' => 'Nazwa drużyn na wyjazdach', 'help' => 'Nowa drużyna dostaje nazwę z kolejnym numerem, np. „Joker 1”, „Joker 2”.', 'default' => 'Joker'],
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
                'color_like' => ['type' => 'color', 'label' => 'Serduszka (polubienia)', 'default' => '#45a0e0'],
                'color_venue' => ['type' => 'color', 'label' => 'Plan treningów: zajęta część boiska', 'default' => '#45a0e0'],
                'color_gym' => ['type' => 'color', 'label' => 'Plan treningów: siłownia', 'default' => '#45a0e0'],
                'gym_icon' => ['type' => 'select', 'label' => 'Plan treningów: ikona siłowni', 'default' => 'dumbbell', 'options' => [
                    'dumbbell' => 'Hantla',
                    'kettlebell' => 'Kettlebell',
                    'plate' => 'Talerz obciążenia',
                    'lifter' => 'Sylwetka ze sztangą',
                ]],
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
                'push_tournaments' => ['type' => 'toggle', 'label' => 'Powiadamiaj o wynikach meczów w turniejach', 'help' => 'Mecze naszych drużyn oraz drużyn, które kibic obserwuje na stronie turnieju.', 'default' => true],
                'push_upcoming' => ['type' => 'toggle', 'label' => 'Przypominaj przed meczem', 'help' => 'Mecze ligowe i turniejowe naszych drużyn oraz obserwowanych drużyn turniejowych. Wymaga harmonogramu (cron) na serwerze.', 'default' => true],
                'push_upcoming_minutes' => ['type' => 'select', 'label' => 'Ile minut przed meczem', 'default' => '15', 'options' => ['10' => '10 minut', '15' => '15 minut', '30' => '30 minut', '60' => '1 godzina']],
            ],
        ],

        'push_tresc' => [
            'label' => 'Powiadomienia: treść',
            'icon' => 'bell',
            'fields' => [
                'push_tpl_article_title' => ['type' => 'text', 'label' => 'Aktualność: tytuł', 'help' => 'Pola: {tytul}, {zajawka}', 'default' => '{tytul}'],
                'push_tpl_article_body' => ['type' => 'text', 'label' => 'Aktualność: treść', 'default' => '{zajawka}'],
                'push_tpl_win_title' => ['type' => 'text', 'label' => 'Wygrany mecz: tytuł', 'help' => 'Pola: {druzyna}, {gospodarz}, {gosc}, {wynik}, {rywal}', 'default' => 'Wygrana! {druzyna}'],
                'push_tpl_loss_title' => ['type' => 'text', 'label' => 'Przegrany mecz: tytuł', 'default' => 'Wynik meczu {druzyna}'],
                'push_tpl_game_body' => ['type' => 'text', 'label' => 'Wynik meczu: treść', 'default' => '{gospodarz} {wynik} {gosc}'],
                'push_tpl_gallery_title' => ['type' => 'text', 'label' => 'Galeria: tytuł', 'help' => 'Pola: {tytul}', 'default' => 'Nowa galeria zdjęć'],
                'push_tpl_gallery_body' => ['type' => 'text', 'label' => 'Galeria: treść', 'default' => '{tytul}'],
                'push_tpl_tournament_title' => ['type' => 'text', 'label' => 'Wynik w turnieju: tytuł', 'help' => 'Pola: {turniej}, {kategoria}, {mecz}, {gospodarz}, {gosc}, {wynik}, {zwyciezca}', 'default' => '{turniej} · {kategoria}'],
                'push_tpl_tournament_body' => ['type' => 'text', 'label' => 'Wynik w turnieju: treść', 'default' => '{gospodarz} {wynik} {gosc}'],
                'push_tpl_upcoming_title' => ['type' => 'text', 'label' => 'Przypomnienie przed meczem: tytuł', 'help' => 'Pola: {minuty}, {godzina}, {gospodarz}, {gosc}, {miejsce}, {nazwa} (drużyna albo turniej i kategoria)', 'default' => 'Za {minuty} min: {nazwa}'],
                'push_tpl_upcoming_body' => ['type' => 'text', 'label' => 'Przypomnienie przed meczem: treść', 'default' => '{gospodarz} – {gosc} · {godzina} {miejsce}'],
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

        'wiadomosci' => [
            'label' => 'Wiadomości',
            'icon' => 'chat',
            'fields' => [
                'messages_enabled' => ['type' => 'toggle', 'label' => 'Wiadomości między użytkownikami', 'help' => 'Wyłączone: znika ikona wiadomości, koperty przy nazwiskach i strona Wiadomości. Rozmowy zostają zapisane.', 'default' => true],
                'messages_team_groups' => ['type' => 'toggle', 'label' => 'Wiadomości do całej drużyny', 'help' => 'Trener, sztab, koordynator i zarząd mogą napisać do wszystkich rodziców, zawodników albo sztabu drużyny naraz.', 'default' => true],
                'messages_rules' => ['type' => 'role_matrix', 'label' => 'Kto do kogo może pisać', 'help' => 'Wiersz: kto pisze, kolumna: do kogo może zacząć rozmowę. Konto z kilkoma rolami łączy ich możliwości. Odpowiedzieć w istniejącej rozmowie można zawsze. Komu w ogóle wolno pisać (i odebranie tego jednej osobie, np. za spam), ustawiasz w „Role i uprawnienia”.', 'default' => 'all'],
            ],
        ],

        'turnieje' => [
            'label' => 'Turnieje: drabinka i karty drużyn',
            'icon' => 'trophy',
            'fields' => [
                'tm_bar_bg' => ['type' => 'color', 'label' => 'Belka meczu: tło', 'default' => '#0f2d6b'],
                'tm_bar_text' => ['type' => 'color', 'label' => 'Belka meczu: tekst', 'default' => '#ffffff'],
                'tm_number_bg' => ['type' => 'color', 'label' => 'Numer meczu: tło', 'default' => '#facc15'],
                'tm_number_text' => ['type' => 'color', 'label' => 'Numer meczu: tekst', 'default' => '#0f172a'],
                'tm_card_from' => ['type' => 'color', 'label' => 'Karty drużyn: kolor górny (gradient)', 'default' => '#1b3f86'],
                'tm_card_to' => ['type' => 'color', 'label' => 'Karty drużyn: kolor dolny (gradient)', 'default' => '#12141a'],
                'tournament_award_names' => ['type' => 'textarea', 'label' => 'Wyróżnienia indywidualne (każde w osobnej linii)', 'help' => 'Lista do wyboru w panelu turnieju (zakładka „Wyróżnienia”). Wyróżnienie dla zawodnika klubu trafia też do osiągnięć w jego profilu.', 'default' => "MVP turnieju\nNajlepsza atakująca\nNajlepsza przyjmująca\nNajlepsza rozgrywająca\nNajlepsza libero\nNajlepsza zagrywająca\nNajlepsza blokująca\nNajlepszy zawodnik\nNajlepszy atakujący\nNajlepszy rozgrywający\nNajlepszy libero"],
            ],
        ],

        'udostepnianie' => [
            'label' => 'Grafiki do mediów społecznościowych',
            'icon' => 'photo',
            'fields' => [
                'share_games' => ['type' => 'toggle', 'label' => 'Przycisk „Grafika” przy wynikach meczów ligowych', 'help' => 'Kibic albo trener tworzy kwadratową grafikę z wynikiem i udostępnia ją z telefonu (Facebook, Instagram, Messenger) albo pobiera.', 'default' => true],
                'share_tournaments' => ['type' => 'toggle', 'label' => 'Przycisk „Grafika” przy meczach turniejowych', 'help' => 'Z nazwą turnieju, kategorią i fazą (grupa, półfinał, finał).', 'default' => true],
                'share_mini' => ['type' => 'toggle', 'label' => 'Przycisk „Grafika” przy wynikach minisiatkówki', 'default' => true],
                'share_mini_names' => ['type' => 'toggle', 'label' => 'Minisiatkówka: imiona dzieci na grafice', 'help' => 'Wyłączone: na grafice są tylko drużyny i miejsca. Imiona zgodnie z ustawieniem „Podpis dziecka”.', 'default' => false],
                'share_articles' => ['type' => 'toggle', 'label' => 'Przycisk „Grafika” przy aktualnościach i relacjach', 'help' => 'Zdjęcie artykułu z tytułem i herbem.', 'default' => true],
                'share_format' => ['type' => 'select', 'label' => 'Format grafiki', 'default' => 'square', 'options' => ['square' => 'Kwadrat 1080×1080 (Facebook, Instagram)', 'portrait' => 'Pion 1080×1350 (Instagram)', 'story' => 'Relacja 1080×1920 (Instagram, Facebook Stories)']],
                'share_bg' => ['type' => 'color', 'label' => 'Tło grafiki', 'default' => '#0f2d6b'],
                'share_bg_to' => ['type' => 'color', 'label' => 'Tło grafiki: drugi kolor (gradient)', 'default' => '#12141a'],
                'share_accent' => ['type' => 'color', 'label' => 'Kolor wyróżnień (wynik, miejsca)', 'default' => '#ffd600'],
                'share_footer' => ['type' => 'text', 'label' => 'Podpis na dole grafiki', 'help' => 'Np. „jokerpila.pl · #JokerPiła”. Puste: adres strony.', 'default' => ''],
            ],
        ],

        'telewizor' => [
            'label' => 'Turnieje: ekran na telewizor',
            'icon' => 'tv',
            'fields' => [
                'tv_show_tables' => ['type' => 'toggle', 'label' => 'Plansza: tabele grup', 'help' => 'Ekran otwierasz w panelu turnieju („Ekran TV”). Duża grupa (6 drużyn i więcej) ma własną planszę, dwie małe stoją obok siebie.', 'default' => true],
                'tv_show_upcoming' => ['type' => 'toggle', 'label' => 'Plansza: najbliższe mecze', 'help' => 'Dla każdej grupy osobno: mecze w trakcie i kolejne według godziny.', 'default' => true],
                'tv_show_results' => ['type' => 'toggle', 'label' => 'Plansza: ostatnie wyniki', 'help' => 'Dla każdej grupy osobno, od najnowszego.', 'default' => true],
                'tv_show_bracket' => ['type' => 'toggle', 'label' => 'Plansza: drabinka i mecze o miejsca', 'default' => true],
                'tv_show_standings' => ['type' => 'toggle', 'label' => 'Plansza: klasyfikacja końcowa', 'help' => 'Pojawia się, gdy znane jest choć jedno miejsce.', 'default' => true],
                'tv_sponsors' => ['type' => 'toggle', 'label' => 'Plansza: sponsorzy', 'help' => 'Logotypy aktywnych sponsorów na końcu każdej rundy.', 'default' => true],
                'tv_live_bar' => ['type' => 'toggle', 'label' => 'Pasek „Teraz gramy” u dołu', 'help' => 'Mecze oznaczone jako „Trwa” z wynikiem, na każdej planszy.', 'default' => true],
                'tv_time_tables' => ['type' => 'select', 'label' => 'Czas planszy z tabelami', 'options' => ['15' => '15 sekund', '20' => '20 sekund', '25' => '25 sekund', '30' => '30 sekund', '40' => '40 sekund', '60' => '1 minuta'], 'default' => '30'],
                'tv_time_other' => ['type' => 'select', 'label' => 'Czas pozostałych plansz', 'options' => ['15' => '15 sekund', '20' => '20 sekund', '25' => '25 sekund', '30' => '30 sekund', '40' => '40 sekund', '60' => '1 minuta'], 'default' => '20'],
                'tv_matches_per_slide' => ['type' => 'select', 'label' => 'Meczów na planszy', 'help' => 'Najbliższe i ostatnie mecze grupy; reszta jest na stronie turnieju.', 'options' => ['6' => '6', '8' => '8', '10' => '10'], 'default' => '8'],
                'tv_bg_top' => ['type' => 'color', 'label' => 'Tło: kolor górny', 'default' => '#0d1d45'],
                'tv_bg_bottom' => ['type' => 'color', 'label' => 'Tło: kolor dolny', 'default' => '#071029'],
                'tv_accent_mode' => ['type' => 'select', 'label' => 'Kolor wyróżnień (kategoria, numery miejsc, pasek czasu)', 'options' => ['category' => 'kolor kategorii', 'custom' => 'jeden kolor poniżej'], 'default' => 'category'],
                'tv_accent' => ['type' => 'color', 'label' => 'Kolor wyróżnień', 'default' => '#38bdf8'],
                'tv_live_color' => ['type' => 'color', 'label' => 'Kolor meczu w trakcie i paska „Teraz gramy”', 'default' => '#dc2626'],
                'tv_footer' => ['type' => 'text', 'label' => 'Napis u dołu ekranu', 'help' => 'Puste = „Wyniki na żywo: adres strony”.', 'default' => ''],
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
                'position_setter' => ['type' => 'textarea', 'label' => 'Dymek po kliknięciu pozycji: Rozgrywający', 'default' => 'Mózg zespołu. Rozgrywający ustawia piłkę do ataku i decyduje, kto skończy akcję.'],
                'position_outside_hitter' => ['type' => 'textarea', 'label' => 'Dymek po kliknięciu pozycji: Przyjmujący', 'default' => 'Wszechstronny zawodnik pierwszej linii, kluczowy zarówno w przyjęciu zagrywki, jak i w ataku.'],
                'position_opposite' => ['type' => 'textarea', 'label' => 'Dymek po kliknięciu pozycji: Atakujący', 'default' => 'Główny snajper zespołu. Atakujący przede wszystkim zdobywa punkty, także z drugiej linii.'],
                'position_middle_blocker' => ['type' => 'textarea', 'label' => 'Dymek po kliknięciu pozycji: Środkowy', 'default' => 'Specjalista od bloku i szybkich ataków ze środka siatki.'],
                'position_libero' => ['type' => 'textarea', 'label' => 'Dymek po kliknięciu pozycji: Libero', 'default' => 'Lider obrony i przyjęcia. Gra w koszulce innego koloru i nie atakuje ponad siatką.'],
            ],
        ],

        'pasek' => [
            'label' => 'Górny pasek',
            'icon' => 'window',
            'fields' => [
                'topbar_enabled' => ['type' => 'toggle', 'label' => 'Pokazuj górny pasek', 'default' => true],
                'topbar_links_left' => ['type' => 'links', 'label' => 'Linki po lewej', 'default' => [
                    ['label' => 'Dla rodziców', 'url' => '/page/dla-rodzicow'],
                    ['label' => 'Dla kibiców', 'url' => '/page/dla-kibicow'],
                    ['label' => 'Nabór', 'url' => '/page/nabor'],
                    ['label' => 'Galerie', 'url' => '/galleries'],
                    ['label' => 'Dokumenty', 'url' => '/documents'],
                ]],
                'topbar_links_right' => ['type' => 'links', 'label' => 'Linki po prawej', 'default' => [
                    ['label' => 'Kontakt', 'url' => '/contact'],
                    ['label' => 'FAQ', 'url' => '/page/faq'],
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
                    ['label' => 'Aktualności', 'url' => '/news'],
                    ['label' => 'Mecze', 'url' => '/matches'],
                    ['label' => 'Tabela', 'url' => '/standings'],
                    ['label' => 'Memoriał', 'url' => '/memorial'],
                    ['label' => 'Drużyny', 'url' => '/teams'],
                    ['label' => 'Młodzież', 'url' => '/page/nabor'],
                    ['label' => 'Klub', 'url' => '/page/o-klubie'],
                ]],
                'nav_button_enabled' => ['type' => 'toggle', 'label' => 'Wyróżniony przycisk na końcu menu', 'default' => true],
                'nav_button_label' => ['type' => 'text', 'label' => 'Wyróżniony przycisk: tekst', 'default' => 'Dołącz'],
                'nav_button_url' => ['type' => 'url', 'label' => 'Wyróżniony przycisk: adres', 'default' => '/register'],
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
                'hero_button_url' => ['type' => 'url', 'label' => 'Pierwszy przycisk: adres', 'default' => '/teams'],
                'hero_second_button_label' => ['type' => 'text', 'label' => 'Drugi przycisk: tekst (puste = ukryty)', 'default' => 'Terminarz meczów'],
                'hero_second_button_url' => ['type' => 'url', 'label' => 'Drugi przycisk: adres', 'default' => '/matches'],
                'home_tile_label' => ['type' => 'text', 'label' => 'Kafelek przy najbliższym meczu: tekst', 'default' => 'Dołącz do klubu'],
                'home_tile_url' => ['type' => 'url', 'label' => 'Kafelek przy najbliższym meczu: adres', 'default' => '/contact'],
                'home_show_countdown' => ['type' => 'toggle', 'label' => 'Zegar odliczający do meczu', 'default' => true],
                'countdown_target' => ['type' => 'select', 'label' => 'Zegar odlicza do', 'help' => 'Gdy seniorzy nie mają zaplanowanego meczu, zegar pokaże najbliższy mecz innej drużyny.', 'default' => 'seniors', 'options' => [
                    'seniors' => 'Najbliższego meczu seniorów',
                    'any' => 'Najbliższego meczu dowolnej drużyny Jokera',
                ]],
                'home_show_stats' => ['type' => 'toggle', 'label' => 'Liczby klubu (drużyny, zawodnicy)', 'default' => true],
                'home_teams_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Drużyny', 'default' => true],
                'home_teams_eyebrow' => ['type' => 'text', 'label' => 'Drużyny: mały napis', 'default' => 'Nasze drużyny'],
                'home_teams_title' => ['type' => 'text', 'label' => 'Drużyny: tytuł', 'default' => 'Od młodzika do seniora'],
                'home_mini_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Minisiatkówka (pod drużynami, gdy są grupy)', 'default' => true],
                'home_mini_eyebrow' => ['type' => 'text', 'label' => 'Minisiatkówka: mały napis', 'default' => 'Pierwsze kroki'],
                'home_mini_title' => ['type' => 'text', 'label' => 'Minisiatkówka: tytuł', 'default' => 'Minisiatkówka'],
                'home_games_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Terminarz i wyniki', 'default' => true],
                'home_games_eyebrow' => ['type' => 'text', 'label' => 'Terminarz: mały napis', 'default' => 'Mecze'],
                'home_games_title' => ['type' => 'text', 'label' => 'Terminarz: tytuł', 'default' => 'Terminarz i wyniki'],
                'home_lineup_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Skład dnia (losowany codziennie, więcej serc = większa szansa)', 'default' => true],
                'home_news_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Aktualności', 'default' => true],
                'home_news_eyebrow' => ['type' => 'text', 'label' => 'Aktualności: mały napis', 'default' => 'Aktualności'],
                'home_news_title' => ['type' => 'text', 'label' => 'Aktualności: tytuł', 'default' => 'Z życia klubu'],
                'home_sponsors_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Sponsorzy', 'default' => true],
                'home_sponsors_title' => ['type' => 'text', 'label' => 'Sponsorzy: napis', 'default' => 'Wspierają nas'],
                'home_alumni_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Wychowankowie', 'default' => true],
                'home_join_enabled' => ['type' => 'toggle', 'label' => 'Sekcja: Zaproszenie na trening', 'default' => true],
                'home_join_eyebrow' => ['type' => 'text', 'label' => 'Zaproszenie: mały napis', 'default' => 'Nabór trwa cały rok'],
                'home_join_title' => ['type' => 'text', 'label' => 'Zaproszenie: tytuł', 'default' => 'Dołącz do Jokera'],
                'home_join_text' => ['type' => 'textarea', 'label' => 'Zaproszenie: tekst', 'default' => 'Zapraszamy chłopców od 7. roku życia. Szkolimy w każdej kategorii wiekowej: od minisiatkówki po seniorską II ligę.'],
                'home_join_button_label' => ['type' => 'text', 'label' => 'Zaproszenie: przycisk', 'default' => 'Zobacz nabór'],
                'home_join_button_url' => ['type' => 'url', 'label' => 'Zaproszenie: adres przycisku', 'default' => '/page/nabor'],
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
                'footer_button_url' => ['type' => 'url', 'label' => 'Przycisk w stopce: adres', 'default' => '/page/nabor'],
                'footer_links_title' => ['type' => 'text', 'label' => 'Stopka: tytuł kolumny z linkami', 'default' => 'Klub'],
                'footer_links' => ['type' => 'links', 'label' => 'Stopka: linki', 'help' => 'Puste = te same linki co w menu i górnym pasku.', 'default' => [
                    ['label' => 'O klubie', 'url' => '/page/o-klubie'],
                    ['label' => 'System szkolenia', 'url' => '/page/system-szkolenia'],
                    ['label' => 'Osiągnięcia', 'url' => '/page/osiagniecia'],
                    ['label' => 'Galerie zdjęć', 'url' => '/galleries'],
                    ['label' => 'Wychowankowie', 'url' => '/alumni'],
                    ['label' => 'Nabór', 'url' => '/page/nabor'],
                    ['label' => 'Plan treningów', 'url' => '/training-schedule'],
                    ['label' => 'Dla zawodników spoza Piły', 'url' => '/page/spoza-pily'],
                    ['label' => 'Wspieraj klub', 'url' => '/support-us'],
                    ['label' => 'Współpraca i sponsoring', 'url' => '/page/wspolpraca'],
                    ['label' => 'Dokumenty', 'url' => '/documents'],
                    ['label' => 'Kontakt', 'url' => '/contact'],
                ]],
                'footer_note' => ['type' => 'text', 'label' => 'Stopka: dopisek na dole (np. nazwa stowarzyszenia, NIP)', 'help' => 'Puste pole ukrywa dopisek.', 'default' => ''],
                'footer_bottom_links' => ['type' => 'links', 'label' => 'Stopka: linki w dolnym pasku', 'help' => 'Np. polityka prywatności. Gdy na stronie nie ma już zdjęć z Unsplash, usuń link do nich.', 'default' => [
                    ['label' => 'Polityka prywatności', 'url' => '/page/polityka-prywatnosci'],
                    ['label' => 'Zdjęcia tematyczne: Unsplash i ich autorzy', 'url' => '/photo-credits'],
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
