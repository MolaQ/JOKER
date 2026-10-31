<?php

namespace App\Support;

use App\Models\Alumnus;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\Comment;
use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\ContentProposal;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\GamePhoto;
use App\Models\HeroSlide;
use App\Models\LeagueStanding;
use App\Models\Page;
use App\Models\PhotoTag;
use App\Models\Player;
use App\Models\PlayerAchievement;
use App\Models\PlayerChangeRequest;
use App\Models\ProfileChangeRequest;
use App\Models\RivalClub;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Setting;
use App\Models\ShopProduct;
use App\Models\Sponsor;
use App\Models\SupportPackage;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use App\Models\TrainingSession;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Dziennik zmian treści: każde dodanie, zmiana i usunięcie zrobione przez zalogowaną osobę
 * trafia do AuditLog z wartościami przed i po. Zadania automatyczne (np. pobieranie terminarzy) się nie zapisują.
 * Role i uprawnienia zapisują się osobno, z czytelnym opisem (User::syncRoles, panel ról).
 */
class ChangeLog
{
    /**
     * Śledzone modele i ich nazwy w dzienniku.
     *
     * @var array<class-string<Model>, string>
     */
    public const MODELS = [
        Article::class => 'Aktualność',
        Page::class => 'Podstrona',
        HeroSlide::class => 'Slajd',
        Document::class => 'Dokument',
        Setting::class => 'Ustawienie strony',
        Team::class => 'Drużyna',
        Player::class => 'Zawodnik',
        PlayerAchievement::class => 'Osiągnięcie zawodnika',
        Alumnus::class => 'Wychowanek',
        User::class => 'Konto',
        TrainingSession::class => 'Trening',
        Game::class => 'Mecz',
        LeagueStanding::class => 'Tabela',
        Season::class => 'Sezon',
        Competition::class => 'Rozgrywki',
        CompetitionLevel::class => 'Poziom rozgrywek',
        RivalClub::class => 'Klub rywala',
        RivalTeam::class => 'Drużyna rywala',
        Tournament::class => 'Turniej',
        TournamentCategory::class => 'Kategoria turnieju',
        TournamentGroup::class => 'Grupa turnieju',
        TournamentParticipant::class => 'Uczestnik turnieju',
        TournamentMatch::class => 'Mecz turniejowy',
        Gallery::class => 'Galeria',
        GalleryPhoto::class => 'Zdjęcie w galerii',
        GamePhoto::class => 'Zdjęcie meczu',
        PhotoTag::class => 'Oznaczenie na zdjęciu',
        Comment::class => 'Komentarz',
        GameComment::class => 'Komentarz do meczu',
        ContentProposal::class => 'Propozycja treści',
        PlayerChangeRequest::class => 'Zgłoszenie zmiany zawodnika',
        ProfileChangeRequest::class => 'Zgłoszenie zmiany profilu',
        Sponsor::class => 'Sponsor',
        Award::class => 'Wyróżnienie',
        SupportPackage::class => 'Pakiet wsparcia',
        ShopProduct::class => 'Gadżet',
    ];

    public const ACTIONS = [
        'model.created' => 'Dodano',
        'model.updated' => 'Zmieniono',
        'model.deleted' => 'Usunięto',
    ];

    /** Pola, których zmiana nic nie mówi (liczniki, znaczniki czasu) albo nie może trafić do dziennika. */
    private const IGNORED = ['id', 'created_at', 'updated_at', 'remember_token', 'views_count', 'likes_count', 'approvals_notified_count', 'approvals_notified_at', 'messages_emailed_at', 'two_factor_secret', 'two_factor_recovery_codes'];

    private const SECRET = ['password'];

    /** Najdłuższa zapisywana wartość jednego pola (np. treść artykułu). */
    private const MAX_LENGTH = 1500;

    public static function register(): void
    {
        foreach (array_keys(self::MODELS) as $model) {
            $model::created(fn (Model $record) => self::record('model.created', $record));
            $model::updated(fn (Model $record) => self::record('model.updated', $record));
            $model::deleted(fn (Model $record) => self::record('model.deleted', $record));
        }
    }

    /** Polskie nazwy częstych pól; pozostałe pokazują się jako nazwa kolumny. */
    private const FIELDS = [
        'name' => 'nazwa', 'title' => 'tytuł', 'slug' => 'adres', 'content' => 'treść', 'excerpt' => 'zajawka', 'body' => 'treść',
        'status' => 'status', 'is_active' => 'aktywne', 'is_published' => 'opublikowane', 'published_at' => 'data publikacji',
        'email' => 'e-mail', 'password' => 'hasło', 'first_name' => 'imię', 'last_name' => 'nazwisko', 'birth_date' => 'data urodzenia',
        'jersey_number' => 'numer', 'position' => 'pozycja', 'team_id' => 'drużyna (nr)', 'user_id' => 'konto (nr)', 'player_id' => 'zawodnik (nr)',
        'photo_path' => 'zdjęcie', 'avatar_path' => 'zdjęcie profilowe', 'logo_path' => 'logo', 'image_path' => 'obrazek', 'path' => 'plik',
        'caption' => 'podpis', 'description' => 'opis', 'url' => 'link', 'video_url' => 'transmisja', 'match_report' => 'relacja',
        'game_date' => 'data meczu', 'opponent' => 'rywal', 'venue' => 'miejsce', 'home_score' => 'sety gospodarzy', 'away_score' => 'sety gości',
        'sets_score' => 'wynik setów', 'is_home' => 'mecz u siebie', 'key' => 'ustawienie', 'value' => 'wartość', 'display_order' => 'kolejność',
        'trainer_id' => 'trener (nr)', 'assistant_trainer_ids' => 'asystenci (nr)', 'starts_at' => 'początek', 'ends_at' => 'koniec', 'weekday' => 'dzień tygodnia',
        'tier' => 'kategoria', 'website' => 'strona www', 'instagram' => 'Instagram', 'additional_info' => 'dodatkowe informacje',
    ];

    public static function fieldLabel(string $field): string
    {
        return self::FIELDS[$field] ?? str_replace('_', ' ', $field);
    }

    public static function typeLabel(?string $type): ?string
    {
        return $type ? (self::MODELS[$type] ?? self::MODELS[Model::getActualClassNameForMorph($type)] ?? class_basename($type)) : null;
    }

    private static function record(string $action, Model $record): void
    {
        if (! Auth::check()) {
            return;
        }

        $changes = self::changes($action, $record);

        if ($changes === []) {
            return;
        }

        $type = self::MODELS[$record::class];
        $name = self::name($record);

        AuditLog::record($action, self::ACTIONS[$action].': '.Str::lower($type).($name ? ' „'.$name.'”' : ''), $record, ['zmiany' => $changes]);
    }

    /**
     * Zmienione pola jako ['pole' => [przed, po]].
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private static function changes(string $action, Model $record): array
    {
        $fields = match ($action) {
            'model.updated' => array_keys($record->getChanges()),
            default => array_keys($record->getAttributes()),
        };

        $changes = [];

        foreach (array_diff($fields, self::IGNORED, array_diff($record->getHidden(), self::SECRET)) as $field) {
            $boolean = $record->hasCast($field, ['bool', 'boolean']);
            $before = $action === 'model.created' ? null : self::value($record->getRawOriginal($field), $boolean);
            $after = $action === 'model.deleted' ? null : self::value($record->getAttributes()[$field] ?? null, $boolean);

            if (in_array($field, self::SECRET, true)) {
                [$before, $after] = [$before === null ? null : '••••', $after === null ? null : '•••• (nowe)'];
            }

            if ($before === $after || ($action !== 'model.updated' && ($before ?? $after) === null)) {
                continue;
            }

            $changes[$field] = [$before, $after];
        }

        return $changes;
    }

    private static function value(mixed $value, bool $boolean = false): mixed
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($boolean && $value !== null) {
            return (bool) $value;
        }

        if ($value === null || $value === '' || is_bool($value) || is_int($value) || is_float($value)) {
            return $value === '' ? null : $value;
        }

        $value = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        return Str::limit($value, self::MAX_LENGTH);
    }

    /**
     * Krótka nazwa rekordu, np. tytuł aktualności albo „Jan Kowalski”.
     */
    private static function name(Model $record): ?string
    {
        try {
            $name = match (true) {
                method_exists($record, 'mediaLabel') => $record->mediaLabel(),
                $record instanceof TournamentMatch => $record->title(),
                $record instanceof TournamentParticipant => $record->name(),
                $record instanceof PhotoTag => $record->label(),
                $record instanceof Player => $record->full_name,
                $record instanceof TrainingSession => trim(($record->team?->name ?? '').' '.$record->weekdayLabel().' '.$record->timeRange()),
                default => $record->getAttribute('name') ?? $record->getAttribute('title') ?? $record->getAttribute('key') ?? $record->getAttribute('caption') ?? $record->getAttribute('content'),
            };
        } catch (Throwable) {
            $name = null;
        }

        $name = is_string($name) ? trim(strip_tags($name)) : null;

        return $name ? Str::limit($name, 80) : '#'.$record->getKey();
    }
}
