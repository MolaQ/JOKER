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

Wyszukiwarka (lupa, klawisz `/` lub Ctrl+K) przeszukuje całą stronę: drużyny, zawodników, sztab,
aktualności, mecze i dokumenty. Nowa treść pojawia się w niej automatycznie.

Jeśli drużyna, artykuł albo slajd nie ma własnego zdjęcia, strona pokazuje darmowe zdjęcie tematyczne
z Unsplash (lista i autorzy: `config/media.php`, strona `/zdjecia`). Wgranie własnego zdjęcia je zastępuje.

## Uruchomienie (dla informatyka)

```bash
composer setup                 # instalacja, .env, migracje, build
php artisan db:seed            # przykładowa treść (wypisze hasło administratora)
composer dev                   # serwer deweloperski
```

Administrator: `php artisan joker:admin marcin.molak@gmail.com` nadaje uprawnienia (lub tworzy konto i wypisuje hasło).

Testy: `php artisan test --compact`.
