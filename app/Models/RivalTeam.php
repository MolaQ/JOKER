<?php

namespace App\Models;

use App\Services\ScheduleImporter;
use Database\Factories\RivalTeamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * Drużyna rywali, wspólna dla wszystkich kategorii. Może należeć do klubu (wspólne logo)
 * i pamięta dawne nazwy z importu, żeby zmiana sponsora w nazwie nie tworzyła nowej drużyny.
 */
class RivalTeam extends Model
{
    /** @use HasFactory<RivalTeamFactory> */
    use HasFactory;

    protected $fillable = [
        'rival_club_id',
        'full_name',
        'short_name',
        'aliases',
        'logo_path',
        'needs_review',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'needs_review' => 'boolean',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(RivalClub::class, 'rival_club_id');
    }

    public function standings(): MorphMany
    {
        return $this->morphMany(LeagueStanding::class, 'competitor');
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'opponent_team_id');
    }

    public function displayName(): string
    {
        return $this->short_name ?: $this->full_name;
    }

    /**
     * Własne logo drużyny, a gdy go nie ma, logo klubu.
     */
    public function logoPath(): ?string
    {
        return $this->logo_path ?: $this->club?->logo_path;
    }

    /**
     * Drużyny grające w bieżącym sezonie albo bez żadnych meczów i tabel (świeżo dodane).
     * Pozostałe to archiwum: grały tylko w dawnych sezonach.
     *
     * @param  Builder<RivalTeam>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $seasonId = Season::current()->value('id');

        $query->where(fn (Builder $query) => $query
            ->whereHas('games', fn (Builder $games) => $games->where('season_id', $seasonId))
            ->orWhereHas('standings', fn (Builder $standings) => $standings->where('season_id', $seasonId))
            ->orWhere(fn (Builder $fresh) => $fresh->whereDoesntHave('games')->whereDoesntHave('standings')));
    }

    /**
     * Drużyna o tej nazwie albo o tej dawnej nazwie (porównanie bez wielkości liter i znaków).
     */
    public static function findByName(string $name): ?self
    {
        $key = ScheduleImporter::key($name);

        return self::all(['id', 'full_name', 'aliases'])->first(
            fn (self $rival) => collect([$rival->full_name, ...($rival->aliases ?? [])])->contains(fn (string $known) => ScheduleImporter::key($known) === $key)
        );
    }

    /**
     * Nazwa bez numeru zespołu na końcu („Energetyk Poznań II” → „energetykpoznan”), do podpowiadania klubów.
     */
    public static function clubKey(string $name): string
    {
        $base = preg_replace('/\s+(\d{1,2}|I{1,3}|IV|V|[A-D])\.?$/u', '', trim($name));

        return ScheduleImporter::key((string) $base);
    }

    /**
     * Łączy tę drużynę z inną (ta sama drużyna pod nową nazwą): mecze, tabele i turnieje
     * przechodzą na $target, a nazwa tej drużyny staje się jej dawną nazwą.
     */
    public function mergeInto(self $target): void
    {
        DB::transaction(function () use ($target) {
            Game::where('opponent_team_id', $this->id)->update(['opponent_team_id' => $target->id]);
            LeagueStanding::where('competitor_type', 'rival_team')->where('competitor_id', $this->id)->update(['competitor_id' => $target->id]);
            TournamentParticipant::where('competitor_type', 'rival_team')->where('competitor_id', $this->id)->update(['competitor_id' => $target->id]);

            $target->update([
                'aliases' => collect($target->aliases ?? [])->merge([$this->full_name, ...($this->aliases ?? [])])
                    ->reject(fn (string $alias) => ScheduleImporter::key($alias) === ScheduleImporter::key($target->full_name))
                    ->unique(fn (string $alias) => ScheduleImporter::key($alias))->values()->all(),
                'logo_path' => $target->logo_path ?: $this->logo_path,
                'rival_club_id' => $target->rival_club_id ?: $this->rival_club_id,
            ]);

            $this->delete();
        });
    }
}
