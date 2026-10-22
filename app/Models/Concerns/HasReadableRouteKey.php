<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Adres w postaci „12-jan-kowalski”: numer zapewnia unikalność, nazwa - czytelność i SEO.
 * Stare adresy z samym numerem nadal działają (przekierowanie na pełny adres).
 */
trait HasReadableRouteKey
{
    abstract protected function routeSlugSource(): string;

    public function getRouteKey(): string
    {
        return $this->getKey().'-'.Str::slug($this->routeSlugSource());
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        return preg_match('/^(\d+)/', (string) $value, $match)
            ? $this->newQuery()->whereKey((int) $match[1])->first()
            : null;
    }
}
