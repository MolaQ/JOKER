<?php

namespace App;

/**
 * Role w klubie. Konto może mieć kilka ról naraz (np. administrator i rodzic).
 * Kolejność przypadków to ważność roli: pierwsza z ról konta jest jego "główną" rolą.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Boss = 'boss';
    case Editor = 'editor';
    case Moderator = 'moderator';
    case Photographer = 'photographer';
    case Trainer = 'trainer';
    case Staff = 'staff';
    case Coordinator = 'coordinator';
    case MiniTrainer = 'mini_trainer';
    case MiniCoordinator = 'mini_coordinator';
    case Parent = 'parent';
    case Player = 'player';
    case Fan = 'fan';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Boss => 'Szef',
            self::Editor => 'Redaktor',
            self::Moderator => 'Moderator',
            self::Photographer => 'Fotograf',
            self::Trainer => 'Trener',
            self::Staff => 'Członek sztabu',
            self::Coordinator => 'Koordynator drużyny',
            self::MiniTrainer => 'Trener mini',
            self::MiniCoordinator => 'Koordynator mini',
            self::Parent => 'Rodzic',
            self::Player => 'Zawodnik',
            self::Fan => 'Kibic',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Wszystkie uprawnienia, nie da się ich odebrać.',
            self::Boss => 'Prawie wszystko. Nadaje role poza rolą administratora.',
            self::Editor => 'Pisze aktualności, dodaje zdjęcia i transmisje.',
            self::Moderator => 'W całym klubie: akceptuje konta, komentarze i propozycje od rodziców i zawodników.',
            self::Photographer => 'Dodaje galerie i zdjęcia meczów, zatwierdza oznaczenia na zdjęciach.',
            self::Trainer => 'Prowadzi swoje drużyny: zawodnicy, mecze, wyniki.',
            self::Staff => 'Kierownik, fizjoterapeuta, statystyk. Ma publiczny profil w sztabie.',
            self::Coordinator => 'Dba o przepływ informacji w swojej drużynie: przyjmuje rodziców, przypisuje ich do dzieci, zatwierdza zgłoszenia drużyny. Nie jest w sztabie.',
            self::MiniTrainer => 'Prowadzi grupy minisiatkówki: dodaje turnieje, wyniki i relacje (do zatwierdzenia). Nie ma dostępu do panelu.',
            self::MiniCoordinator => 'Pomaga w minisiatkówce: dodaje turnieje, wyniki i relacje (do zatwierdzenia). Nie ma dostępu do panelu.',
            self::Parent => 'Opiekun konkretnego zawodnika lub kilku zawodników.',
            self::Player => 'Zawodnik z profilem w kadrze.',
            self::Fan => 'Konto bez funkcji w klubie: serduszka i komentarze.',
        };
    }

    /**
     * Uprawnienia, z którymi rola startuje. Później zmienia się je w panelu "Role i uprawnienia".
     *
     * @return list<Permission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin, self::Boss => Permission::cases(),
            self::Editor => [Permission::Articles, Permission::Media, Permission::Homepage, Permission::Documents, Permission::Comments, Permission::ContentProposals],
            self::Trainer => [Permission::Teams, Permission::Trainings, Permission::Games, Permission::Tournaments, Permission::MiniVolleyball, Permission::Media, Permission::Proposals, Permission::ActivateAccounts],
            self::Moderator => [Permission::WriteComments, Permission::WriteMessages, Permission::Comments, Permission::ContentProposals, Permission::Proposals, Permission::ActivateAccounts, Permission::AllTeams],
            self::Photographer => [Permission::WriteComments, Permission::WriteMessages, Permission::Media, Permission::PhotoTags],
            self::Staff => [Permission::Trainings],
            self::Coordinator => [Permission::WriteComments, Permission::WriteMessages, Permission::Teams, Permission::MiniVolleyball, Permission::ActivateAccounts, Permission::Proposals],
            self::MiniTrainer, self::MiniCoordinator => [Permission::WriteComments, Permission::WriteMessages, Permission::MiniSubmit],
            default => [],
        };
    }
}
