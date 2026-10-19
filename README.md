# Joker Piła - strona klubowa

Strona klubu siatkarskiego Joker Piła (Laravel 12 + Livewire, Tailwind CSS 4).

## Obsługa strony (dla opiekunów)

Wszystko zmieniasz w panelu: zaloguj się i kliknij **Panel** w prawym górnym rogu.

| Chcę... | Gdzie w panelu |
| --- | --- |
| dodać aktualność | Aktualności |
| wpisać wynik albo dodać mecz | Mecze i wyniki |
| zmienić zdjęcie drużyny, trenera lub sztab | Drużyny i sztab → Edytuj |
| dodać zawodnika | Zawodnicy |
| zmienić slajd na stronie głównej | Slajdy na stronie głównej |
| dać komuś dostęp do panelu | Użytkownicy → rola „Administrator” lub „Trener” |
| dodać osobę do sztabu bez dostępu do panelu | Użytkownicy → rola „Członek sztabu” |
| wrzucić regulamin lub zgodę do pobrania | Dokumenty (zaznacz „publiczny”) |
| zmienić kolory, logo, nazwę klubu | Wygląd i ustawienia → Ogólne / Kolory |
| zmienić linki w żółtym pasku lub w menu | Wygląd i ustawienia → Górny pasek / Menu główne |
| zmienić teksty na stronie głównej, ukryć sekcję | Wygląd i ustawienia → Strona główna |
| zmienić telefon, e-mail, adres, media społecznościowe | Wygląd i ustawienia → Kontakt i stopka |
| pobierać terminarz i wyniki ze strony związku (WZPS) | Rozgrywki → Edytuj → wklej adres strony z meczami i zaznacz nasze drużyny (z nazwą, pod którą grają w tych rozgrywkach) |
| ustawić, do jakiego meczu odlicza zegar | Wygląd i ustawienia → Strona główna |
| dodać stronę typu „Dla rodziców”, „FAQ” | Podstrony (adres: /strona/nazwa, potem dodaj link w pasku lub menu) |

„Wygląd i ustawienia” oraz „Podstrony” widzi tylko administrator. Każda zakładka ma przycisk
„Przywróć domyślne”. Nowe ustawienie programista dodaje w `config/site.php` i od razu pojawia się w panelu.

Wyszukiwarka (lupa, klawisz `/` lub Ctrl+K) przeszukuje całą stronę: drużyny, zawodników, sztab,
aktualności, mecze i dokumenty. Nowa treść pojawia się w niej automatycznie.

Jeśli drużyna, artykuł albo slajd nie ma własnego zdjęcia, strona pokazuje darmowe zdjęcie tematyczne
z Unsplash (lista i autorzy: `config/media.php`, strona `/zdjecia`). Wgranie własnego zdjęcia je zastępuje.

## Uruchomienie (dla informatyka)

Terminarze z rozgrywek z wpisanym adresem odświeżają się same co 3 godziny, jeśli na serwerze działa
cron: `* * * * * cd /sciezka/do/projektu && php artisan schedule:run >> /dev/null 2>&1`.
Ręcznie: `php artisan joker:sync-schedules` albo przycisk „Pobierz terminarz” w panelu (Rozgrywki).

```bash
composer setup                 # instalacja, .env, migracje, build
php artisan db:seed            # przykładowa treść (wypisze hasło administratora)
composer dev                   # serwer deweloperski
```

Administrator: `php artisan joker:admin marcin.molak@gmail.com` nadaje uprawnienia (lub tworzy konto i wypisuje hasło).

Testy: `php artisan test --compact`.
