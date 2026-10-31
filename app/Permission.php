<?php

namespace App;

/**
 * Uprawnienia w panelu klubu. Role dostają je w panelu "Role i uprawnienia",
 * a pojedynczemu kontu można je dodatkowo nadać albo odebrać.
 */
enum Permission: string
{
    case Articles = 'articles.manage';
    case Media = 'media.manage';
    case Homepage = 'homepage.manage';
    case Comments = 'comments.moderate';
    case ContentProposals = 'proposals.content';
    case PhotoTags = 'photos.tags';
    case Teams = 'teams.manage';
    case AllTeams = 'teams.all';
    case Trainings = 'trainings.manage';
    case Games = 'games.manage';
    case Tournaments = 'tournaments.manage';
    case MiniVolleyball = 'mini.manage';
    case MiniSubmit = 'mini.submit';
    case Competitions = 'competitions.manage';
    case Proposals = 'proposals.review';
    case Documents = 'documents.manage';
    case Sponsors = 'sponsors.manage';
    case ActivateAccounts = 'users.activate';
    case Users = 'users.manage';
    case Roles = 'roles.manage';
    case Site = 'site.manage';
    case Statistics = 'statistics.view';
    case AuditLog = 'audit.view';
    case Import = 'import.run';
    case WriteComments = 'comments.write';
    case WriteMessages = 'messages.write';

    public function label(): string
    {
        return match ($this) {
            self::Articles => 'Aktualności',
            self::Media => 'Transmisje i galerie',
            self::Homepage => 'Slajdy na stronie głównej',
            self::Comments => 'Moderacja komentarzy i zgłoszonych wiadomości',
            self::ContentProposals => 'Zatwierdzanie artykułów, zdjęć i transmisji od rodziców i zawodników',
            self::PhotoTags => 'Zatwierdzanie wszystkich oznaczeń na zdjęciach',
            self::Teams => 'Drużyny, sztab i zawodnicy',
            self::AllTeams => 'Wszystkie drużyny, nie tylko własne',
            self::Trainings => 'Plan treningów',
            self::Games => 'Mecze, wyniki i tabele',
            self::Tournaments => 'Turnieje',
            self::MiniVolleyball => 'Minisiatkówka: dzieci, wyjazdy i wyniki',
            self::MiniSubmit => 'Minisiatkówka: zgłaszanie turniejów, wyników i relacji (do zatwierdzenia)',
            self::Competitions => 'Rozgrywki, rywale, sezony',
            self::Proposals => 'Zatwierdzanie zmian od zawodników i rodziców',
            self::Documents => 'Dokumenty',
            self::Sponsors => 'Sponsorzy, pakiety sponsorskie i gadżety',
            self::ActivateAccounts => 'Aktywacja kont',
            self::Users => 'Wszystkie konta użytkowników',
            self::Roles => 'Role i uprawnienia',
            self::Site => 'Wygląd, ustawienia i podstrony',
            self::Statistics => 'Statystyki odwiedzin',
            self::AuditLog => 'Dziennik zmian (kto, co i kiedy zmienił)',
            self::Import => 'Import z plików',
            self::WriteComments => 'Pisanie komentarzy',
            self::WriteMessages => 'Pisanie wiadomości',
        };
    }

    public function hint(): ?string
    {
        return match ($this) {
            self::AllTeams => 'Bez tego trener widzi tylko drużyny, które prowadzi, i ich zawodników.',
            self::ActivateAccounts => 'Trener aktywuje tylko konta związane ze swoimi drużynami.',
            self::Trainings => 'Trener i członek sztabu układają plan tylko swoich drużyn.',
            self::PhotoTags => 'Bez tego trener zatwierdza tylko oznaczenia zawodników swoich drużyn.',
            self::Roles => 'Kto nie jest administratorem, nie nada roli administratora.',
            self::MiniSubmit => 'Zakładka „Zgłoś” na stronie minisiatkówki. Zgłoszenia czekają w „Do zatwierdzenia”, chyba że zgłasza ktoś z uprawnieniem „Minisiatkówka: dzieci, wyjazdy i wyniki”.',
            self::WriteComments => 'Komentarze pod aktualnościami i meczami. Odbierz je, gdy ktoś łamie zasady.',
            self::WriteMessages => 'Wiadomości do innych osób i grup na stronie. Odbierz je, gdy ktoś wysyła spam albo łamie zasady.',
            default => null,
        };
    }

    /**
     * Uprawnienie do korzystania ze strony publicznej, a nie do panelu klubu.
     */
    public function isPublic(): bool
    {
        return in_array($this, [self::WriteComments, self::WriteMessages, self::MiniSubmit], true);
    }

    public function group(): string
    {
        return match ($this) {
            self::Articles, self::Media, self::Homepage, self::Comments, self::ContentProposals, self::PhotoTags => 'Treści',
            self::Teams, self::AllTeams, self::Trainings, self::Games, self::Tournaments, self::MiniVolleyball, self::MiniSubmit, self::Competitions, self::Proposals => 'Drużyny i rozgrywki',
            self::Documents, self::Sponsors => 'Klub',
            self::WriteComments, self::WriteMessages => 'Korzystanie ze strony',
            default => 'Konta i strona',
        };
    }
}
