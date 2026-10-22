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
    case Teams = 'teams.manage';
    case AllTeams = 'teams.all';
    case Games = 'games.manage';
    case Tournaments = 'tournaments.manage';
    case Competitions = 'competitions.manage';
    case Proposals = 'proposals.review';
    case Documents = 'documents.manage';
    case Sponsors = 'sponsors.manage';
    case ActivateAccounts = 'users.activate';
    case Users = 'users.manage';
    case Roles = 'roles.manage';
    case Site = 'site.manage';
    case Statistics = 'statistics.view';
    case Import = 'import.run';

    public function label(): string
    {
        return match ($this) {
            self::Articles => 'Aktualności',
            self::Media => 'Transmisje i galerie',
            self::Homepage => 'Slajdy na stronie głównej',
            self::Comments => 'Moderacja komentarzy',
            self::ContentProposals => 'Zatwierdzanie artykułów, zdjęć i transmisji od rodziców i zawodników',
            self::Teams => 'Drużyny, sztab i zawodnicy',
            self::AllTeams => 'Wszystkie drużyny, nie tylko własne',
            self::Games => 'Mecze, wyniki i tabele',
            self::Tournaments => 'Turnieje',
            self::Competitions => 'Rozgrywki, rywale, sezony',
            self::Proposals => 'Zatwierdzanie zmian od zawodników i rodziców',
            self::Documents => 'Dokumenty',
            self::Sponsors => 'Sponsorzy',
            self::ActivateAccounts => 'Aktywacja kont',
            self::Users => 'Wszystkie konta użytkowników',
            self::Roles => 'Role i uprawnienia',
            self::Site => 'Wygląd, ustawienia i podstrony',
            self::Statistics => 'Statystyki odwiedzin',
            self::Import => 'Import z plików',
        };
    }

    public function hint(): ?string
    {
        return match ($this) {
            self::AllTeams => 'Bez tego trener widzi tylko drużyny, które prowadzi, i ich zawodników.',
            self::ActivateAccounts => 'Trener aktywuje tylko konta związane ze swoimi drużynami.',
            self::Roles => 'Kto nie jest administratorem, nie nada roli administratora.',
            default => null,
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Articles, self::Media, self::Homepage, self::Comments, self::ContentProposals => 'Treści',
            self::Teams, self::AllTeams, self::Games, self::Tournaments, self::Competitions, self::Proposals => 'Drużyny i rozgrywki',
            self::Documents, self::Sponsors => 'Klub',
            default => 'Konta i strona',
        };
    }
}
