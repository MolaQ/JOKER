<?php

namespace App\Models;

use App\Models\Concerns\HasMatchMedia;
use App\Models\Concerns\Likeable;
use App\Services\ScheduleImporter;
use App\Support\SiteSettings;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Game extends Model
{
    /** Skąd pochodzi wynik (kolumna result_source, maks. 16 znaków). */
    public const RESULT_MANUAL = 'manual';

    public const RESULT_SITE = 'ws';

    public const RESULT_WZPS = 'wzps';

    /** Rodzaje meczów: ligowy, towarzyski (sparing) i rozegrany w ramach turnieju. */
    public const KIND_LEAGUE = 'league';

    public const KIND_FRIENDLY = 'friendly';

    public const KIND_TOURNAMENT = 'tournament';

    /** @var array<string, string> */
    public const KINDS = [self::KIND_LEAGUE => 'Ligowy', self::KIND_FRIENDLY => 'Sparing', self::KIND_TOURNAMENT => 'Turniejowy'];

    /** @use HasFactory<GameFactory> */
    use HasFactory, HasMatchMedia, Likeable;

    protected $fillable = [
        'team_id',
        'season_id',
        'competition_id',
        'external_id',
        'opponent',
        'opponent_team_id',
        'is_home',
        'game_date',
        'venue',
        'league',
        'round_label',
        'kind',
        'tournament_id',
        'status',
        'home_score',
        'away_score',
        'home_points',
        'away_points',
        'sets_score',
        'result_source',
        'result_note',
        'match_report',
        'video_url',
    ];

    protected function casts(): array
    {
        return [
            'game_date' => 'datetime',
            'is_home' => 'boolean',
            'sets_score' => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function opponentTeam(): BelongsTo
    {
        return $this->belongsTo(RivalTeam::class, 'opponent_team_id');
    }

    /**
     * Nazwa przeciwnika do wyświetlenia: preferuje powiązaną drużynę rywala,
     * w przeciwnym razie wolny tekst wpisany ręcznie.
     */
    public function opponentDisplayName(): string
    {
        return $this->opponentTeam?->full_name ?: $this->opponent;
    }

    public function opponentShortName(): string
    {
        return $this->opponentTeam?->displayName() ?: $this->opponent;
    }

    /**
     * Liczba setów wygranych/przegranych przez naszą drużynę w tym meczu.
     *
     * @return array{0: int, 1: int}|null
     */
    public function ourSetsResult(): ?array
    {
        if (is_null($this->home_score) || is_null($this->away_score)) {
            return null;
        }

        return $this->is_home
            ? [$this->home_score, $this->away_score]
            : [$this->away_score, $this->home_score];
    }

    /**
     * Mecz dwóch drużyn z naszymi zawodnikami (np. KS ASBL Piła - LOMS Joker Piła).
     */
    public function isClubDerby(): bool
    {
        $site = app(SiteSettings::class);
        $keys = $site->clubNameKeys();

        if (! $site['module_derby'] || $keys === []) {
            return false;
        }

        return collect([$this->opponentTeam?->full_name, $this->opponent])
            ->filter()
            ->contains(fn (string $name): bool => Str::contains(ScheduleImporter::key($name), $keys));
    }

    /**
     * Wynik zakończonego meczu z naszej strony: 'win', 'loss' albo 'derby' (wygrała pilska siatkówka).
     */
    public function outcome(): ?string
    {
        $result = $this->ourSetsResult();

        if (! $result || ! $this->isFinished()) {
            return null;
        }

        return match (true) {
            $this->isClubDerby() => 'derby',
            $result[0] > $result[1] => 'win',
            default => 'loss',
        };
    }

    /**
     * Podpis naszej drużyny ustawiony w rozgrywkach meczu (nazwa klubu + plakietka drużyny).
     *
     * @return array{name: string, badge: ?string}
     */
    public function ourLabel(): array
    {
        if ($this->competition) {
            return $this->competition->teamLabel($this->team);
        }

        return ['name' => app(SiteSettings::class)['club_name'], 'badge' => $this->team?->name];
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    /**
     * Najnowszy opublikowany artykuł o meczu (wymaga załadowanej relacji articles).
     */
    public function latestArticle(): ?Article
    {
        return $this->articles
            ->filter(fn (Article $article): bool => $article->isPublished())
            ->sortByDesc('published_at')
            ->first();
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function isFriendly(): bool
    {
        return $this->kind === self::KIND_FRIENDLY;
    }

    /**
     * Nazwa rozgrywek do opisu meczu: liga, „Sparing” albo nazwa turnieju.
     */
    public function competitionLabel(): ?string
    {
        return match (true) {
            $this->kind === self::KIND_FRIENDLY => __('Sparing'),
            $this->tournament_id !== null => $this->tournament?->fullName() ?? __('Turniej'),
            default => $this->competition?->name ?? $this->league,
        };
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GameComment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(GamePhoto::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(GameComment::class)->where('is_approved', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('game_date', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('game_date');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished')
            ->orderBy('game_date', 'desc');
    }

    /**
     * Data i godzina meczu do wyświetlenia. Mecze z importu bez podanej godziny mają 00:00.
     */
    public function kickoffLabel(string $dateFormat = 'D, d M'): string
    {
        if (! $this->game_date) {
            return __('Termin do ustalenia');
        }

        $time = $this->game_date->format('H:i');

        return $this->game_date->translatedFormat($dateFormat).' · '.($time === '00:00' ? __('godz. do ustalenia') : $time);
    }

    /**
     * Gospodarze i goście w kolejności z boiska, np. „LOMS Joker Piła – MKS Kalisz”.
     *
     * @return array{0: string, 1: string}
     */
    public function sidesNames(): array
    {
        $ours = $this->ourLabel()['name'];
        $opponent = (string) $this->opponentDisplayName();

        return $this->is_home ? [$ours, $opponent] : [$opponent, $ours];
    }

    public function mediaLabel(): string
    {
        [$home, $away] = $this->sidesNames();
        $competition = $this->competitionLabel();

        return collect([
            $this->game_date?->format('d.m.Y H:i'),
            $this->team?->name,
            $home.' – '.$away.($competition ? ' ('.$competition.')' : ''),
        ])->filter()->implode(' · ');
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled' && $this->game_date > now();
    }
}
