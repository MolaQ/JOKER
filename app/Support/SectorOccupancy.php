<?php

namespace App\Support;

/**
 * Zajętość sektorów hali w czasie: główne boisko zajmuje wszystkie trzy sektory naraz.
 */
class SectorOccupancy
{
    /** @var array<string, list<array{0: int, 1: int}>> sektor → przedziały [początek, koniec) w sekundach */
    private array $busy = [];

    /**
     * Fizyczne części hali, które zajmuje mecz na danym sektorze.
     *
     * @return list<string>
     */
    public static function physical(string $sector): array
    {
        return $sector === 'main' ? ['court1', 'court2', 'court3'] : [$sector];
    }

    public function add(?string $sector, int $starts, int $minutes): void
    {
        if ($sector === null) {
            return;
        }

        foreach (self::physical($sector) as $part) {
            $this->busy[$part][] = [$starts, $starts + $minutes * 60];
        }
    }

    /**
     * Najwcześniejszy początek od podanej chwili, przy którym mecz o podanej długości mieści się na sektorze.
     */
    public function earliest(string $sector, int $from, int $minutes): int
    {
        $starts = $from;

        do {
            $moved = false;

            foreach (self::physical($sector) as $part) {
                foreach ($this->busy[$part] ?? [] as [$busyFrom, $busyTo]) {
                    if ($starts < $busyTo && $starts + $minutes * 60 > $busyFrom) {
                        $starts = $busyTo;
                        $moved = true;
                    }
                }
            }
        } while ($moved);

        return $starts;
    }
}
