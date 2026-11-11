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
Ręcznie: `php artisan joker:sync-schedules` albo przycisk „Pobierz terminarz” w panelu (Rozgrywki). Wyniki i tabele (wszystkie etapy i grupy) pochodzą z wielkopolskasiatkowka.com.pl, a gdy jej brak - z WZPS; godziny, hale i numery meczów z WZPS. Wynik wpisany ręcznie w panelu nie jest nadpisywany. `php artisan migrate:fresh --seed` zakłada prawdziwe drużyny i rozgrywki WZPS i od razu pobiera dane.

```bash
composer setup                 # instalacja, .env, migracje, build
php artisan db:seed            # przykładowa treść (wypisze hasło administratora)
composer dev                   # serwer deweloperski
```

Administrator: `php artisan joker:admin marcin.molak@gmail.com` nadaje uprawnienia (lub tworzy konto i wypisuje hasło).

Testy: `php artisan test --compact`.

### Kopie zapasowe na serwerze

`narzedzia/kopia-na-serwerze.sh` uruchamiany przez `instaluj-na-serwerze.sh` (cron co kilka minut) robi kopię
bazy, pliku `.env` i wgranych zdjęć (`storage/app/public`) raz na dobę po 3:00 w nocy, a bazy i `.env` także przed
każdą instalacją nowej paczki. Nowa kopia powstaje tylko, gdy coś się zmieniło. Kopie leżą w folderze `kopie-joker`
obok folderu strony (np. `domains/jokerpila.pl/kopie-joker`), przebieg w `kopie-joker/kopie.log`. Zostaje 14 kopii
bazy i `.env` oraz 3 kopie zdjęć. Bazę przywraca się importem `baza-*.sql.gz` w phpMyAdmin, zdjęcia rozpakowaniem
`zdjecia-*.tar` w folderze `storage/app`.
