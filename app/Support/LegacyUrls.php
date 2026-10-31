<?php

namespace App\Support;

/**
 * Stare adresy strony (polskie i pierwsze angielskie) → aktualne angielskie.
 * Każdy człon ścieżki tłumaczymy osobno, a stary format profilu „12-jan-kowalski” poprawia potem
 * RedirectToCanonicalUrl („12/jan-kowalski”).
 */
class LegacyUrls
{
    /** Pierwszy człon starego adresu => nowy. */
    public const PREFIXES = [
        'jezyk' => 'language',
        'wychowankowie' => 'alumni',
        'plan-treningow' => 'training-schedule',
        'wspieraj-klub' => 'support-us',
        'galerie' => 'galleries',
        'druzyny' => 'teams',
        'aktualnosci' => 'news',
        'turnieje' => 'tournaments',
        'zawodnicy' => 'players',
        'sztab' => 'staff',
        'trenerzy' => 'staff',
        'osoby' => 'family',
        'mecze' => 'matches',
        'schedule' => 'matches',
        'tabele' => 'standings',
        'szukaj' => 'search',
        'dokumenty' => 'documents',
        'zdjecia' => 'photo-credits',
        'strona' => 'page',
        'kontakt' => 'contact',
        'rejestracja' => 'register',
        'haslo' => 'password',
        'moj-profil' => 'my-profile',
        'profile' => 'my-profile',
        'wiadomosci' => 'messages',
        'zgody' => 'consents',
    ];

    /** Dalsze człony (podstrony). */
    private const SEGMENTS = [
        'mecze' => 'matches',
        'komentarze' => 'comments',
        'kalendarz.ics' => 'calendar.ics',
        'indeks.json' => 'index.json',
        'pobierz' => 'download',
        'przypomnij' => 'forgot',
        'nowe' => 'reset',
        'moje-dane' => 'my-data',
    ];

    public static function translate(string $uri): string
    {
        [$path, $query] = array_pad(explode('?', $uri, 2), 2, null);
        $segments = explode('/', trim($path, '/'));

        foreach ($segments as $index => $segment) {
            $segments[$index] = $index === 0
                ? (self::PREFIXES[$segment] ?? $segment)
                : (self::SEGMENTS[$segment] ?? $segment);
        }

        return '/'.implode('/', $segments).($query !== null && $query !== '' ? '?'.$query : '');
    }
}
