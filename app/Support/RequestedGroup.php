<?php

namespace App\Support;

use App\Models\MiniGroup;
use App\Models\Team;
use Closure;
use Illuminate\Support\Collection;

/**
 * Pole „Drużyna lub grupa” przy zakładaniu konta (i przy ponownej prośbie po odrzuceniu):
 * drużyny klubu oraz grupy minisiatkówki („mini-ID”), żeby rodzic dziecka z mini miał co wybrać.
 */
class RequestedGroup
{
    public const MINI_PREFIX = 'mini-';

    /**
     * @return array{teams: Collection<int, Team>, mini: Collection<int, MiniGroup>}
     */
    public static function options(): array
    {
        return [
            'teams' => Team::orderBy('display_order')->get(['id', 'name']),
            'mini' => app(SiteSettings::class)['module_mini'] ? MiniGroup::where('is_active', true)->ordered()->get(['id', 'name']) : collect(),
        ];
    }

    /**
     * Reguła walidacji: identyfikator drużyny albo „mini-ID” aktywnej grupy mini.
     */
    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            [$team, $mini] = self::resolve($value);
            if ($team === null && $mini === null) {
                $fail(__('Wybierz drużynę albo grupę z listy.'));
            }
        };
    }

    /**
     * @return array{0: ?int, 1: ?int} [requested_team_id, requested_mini_group_id]
     */
    public static function resolve(mixed $value): array
    {
        $value = (string) $value;

        if (str_starts_with($value, self::MINI_PREFIX)) {
            $id = (int) substr($value, strlen(self::MINI_PREFIX));

            return [null, $id > 0 && self::options()['mini']->contains('id', $id) ? $id : null];
        }

        return [ctype_digit($value) && Team::whereKey((int) $value)->exists() ? (int) $value : null, null];
    }

    /**
     * Wartość pola dla konta (np. przy ponownej prośbie): „12” albo „mini-3”.
     */
    public static function valueFor(?int $teamId, ?int $miniGroupId): string
    {
        return $miniGroupId ? self::MINI_PREFIX.$miniGroupId : (string) $teamId;
    }
}
