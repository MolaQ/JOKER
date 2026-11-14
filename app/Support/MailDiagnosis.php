<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Tłumaczy błąd wysyłki poczty (Symfony Mailer) na wskazówkę po polsku: co poprawić w .env albo w DirectAdminie.
 */
class MailDiagnosis
{
    public static function explain(string $error): string
    {
        $text = Str::lower($error);

        return match (true) {
            Str::contains($text, ['535', 'authentication failed', 'auth', 'username and password', 'credentials']) => 'Serwer poczty odrzucił login lub hasło: sprawdź MAIL_USERNAME (pełny adres skrzynki) i MAIL_PASSWORD w .env. Hasło z cudzysłowem lub znakiem # wpisz w cudzysłowie.',
            Str::contains($text, ['connection refused', 'connection could not be established', 'timed out', 'timeout', 'network is unreachable']) => 'Strona nie może połączyć się z serwerem poczty: sprawdź MAIL_HOST i MAIL_PORT (465 z MAIL_SCHEME=smtps albo 587 z MAIL_SCHEME=smtp).',
            Str::contains($text, ['getaddrinfo', 'name or service not known', 'no such host', 'php_network_getaddresses']) => 'Nie ma takiego serwera poczty: popraw MAIL_HOST w .env (np. adres serwera z DirectAdmina).',
            Str::contains($text, ['certificate', 'ssl', 'tls', 'crypto']) => 'Problem z szyfrowaniem połączenia: ustaw MAIL_HOST na nazwę serwera z certyfikatu (z DirectAdmina) albo zmień port i MAIL_SCHEME.',
            Str::contains($text, ['sender', '553', '550', '554', 'not allowed', 'relay', 'rejected']) => 'Serwer poczty nie przyjął nadawcy lub odbiorcy: MAIL_FROM_ADDRESS musi być tą samą skrzynką, na którą logujesz się w MAIL_USERNAME.',
            Str::contains($text, ['quota', 'limit', 'too many']) => 'Serwer poczty odrzucił wysyłkę przez limit: spróbuj za jakiś czas albo sprawdź limity skrzynki w DirectAdminie.',
            default => 'Sprawdź ustawienia MAIL_ w pliku .env na serwerze.',
        };
    }

    /**
     * Krótki opis do komunikatu w panelu: błąd serwera poczty i wskazówka.
     */
    public static function summary(string $error): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $error)), 200).' → '.self::explain($error);
    }
}
