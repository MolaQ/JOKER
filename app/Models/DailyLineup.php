<?php

namespace App\Models;

use App\PlayerPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Skład dnia: sześciu zawodników ze wszystkich roczników i trener, losowani raz na dzień.
 * Szansa rośnie z liczbą serduszek na profilu (waga: polubienia + 1, więc każdy może trafić do składu).
 */
class DailyLineup extends Model
{
    /**
     * Miejsca na boisku od siatki: pierwsza linia (przyjmujący, środkowy, atakujący),
     * druga linia (przyjmujący, libero, rozgrywający).
     *
     * @var list<PlayerPosition>
     */
    public const SLOTS = [
        PlayerPosition::OutsideHitter,
        PlayerPosition::MiddleBlocker,
        PlayerPosition::Opposite,
        PlayerPosition::OutsideHitter,
        PlayerPosition::Libero,
        PlayerPosition::Setter,
    ];

    protected $fillable = ['date', 'players', 'coach_id', 'captain_id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'players' => 'array',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * Dzisiejszy skład; pierwsze wejście danego dnia go losuje.
     */
    public static function today(): self
    {
        $date = Carbon::today()->toDateString();

        if ($lineup = static::whereDate('date', $date)->first()) {
            return $lineup;
        }

        try {
            return static::create(['date' => $date, ...static::draw()]);
        } catch (QueryException) {
            // Dwa wejścia w tej samej chwili: zostaje skład zapisany przez pierwsze.
            return static::whereDate('date', $date)->firstOrFail();
        }
    }

    /**
     * @return array{players: list<array{slot: string, player_id: int|null}>, coach_id: int|null, captain_id: int|null}
     */
    public static function draw(): array
    {
        $chosen = [];
        $slots = [];

        foreach (self::SLOTS as $position) {
            $player = static::pick(
                Player::query()->where('is_active', true)->whereNotNull('team_id')
                    ->where('position', $position)->whereNotIn('id', $chosen)
                    ->withCount('likes')->get(['id'])
            );

            $slots[] = ['slot' => $position->value, 'player_id' => $player?->id];

            if ($player) {
                $chosen[] = $player->id;
            }
        }

        $coach = static::pick(
            User::query()->where('is_active', true)
                ->where(fn (Builder $query) => $query->whereHas('headCoachTeams')->orWhereHas('assistantTeams'))
                ->withCount('receivedLikes as likes_count')->get(['id'])
        );

        return ['players' => $slots, 'coach_id' => $coach?->id, 'captain_id' => static::captainAmong($chosen)];
    }

    /**
     * Opaska tylko dla jednego: przy kilku kapitanach dostaje ją ten ze starszej drużyny
     * (kolejność drużyn jak na stronie: seniorzy, juniorzy, juniorzy II, ..., młodzicy II).
     *
     * @param  list<int>  $playerIds
     */
    public static function captainAmong(array $playerIds): ?int
    {
        return Player::with('team')->whereIn('id', $playerIds)->get()
            ->filter(fn (Player $player) => $player->isCaptain())
            ->sortBy(fn (Player $player) => [$player->team?->display_order ?? PHP_INT_MAX, $player->team_id])
            ->first()?->id;
    }

    /**
     * Losowanie z wagą „polubienia + 1”.
     *
     * @param  Collection<int, Model>  $candidates  rekordy z likes_count
     */
    private static function pick(Collection $candidates): ?Model
    {
        $total = $candidates->sum(fn (Model $candidate) => (int) $candidate->likes_count + 1);

        if ($total === 0) {
            return null;
        }

        $ticket = random_int(1, $total);

        foreach ($candidates as $candidate) {
            $ticket -= (int) $candidate->likes_count + 1;

            if ($ticket <= 0) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Zawodnicy w kolejności miejsc na boisku (null, gdy na pozycję nie było kogo wylosować albo zawodnika usunięto).
     *
     * @return list<array{position: PlayerPosition, player: Player|null}>
     */
    public function lineup(): array
    {
        $players = Player::with('team')->whereIn('id', collect($this->players)->pluck('player_id')->filter())->get()->keyBy('id');

        return collect($this->players)->map(fn (array $slot) => [
            'position' => PlayerPosition::from($slot['slot']),
            'player' => $players->get($slot['player_id']),
        ])->all();
    }
}
