<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Adres w postaci „12/jan-kowalski”: numer zapewnia unikalność, nazwa - czytelność i SEO.
 * Stare adresy („12”, „12-jan-kowalski”) nadal działają (przekierowanie na pełny adres).
 * Trasa musi dopuszczać ukośnik w parametrze: ->where('player', Slug::ROUTE_PATTERN).
 */
trait HasReadableRouteKey
{
    abstract protected function routeSlugSource(): string;

    public function getRouteKey(): string
    {
        $slug = Str::slug($this->routeSlugSource());

        return $slug === '' ? (string) $this->getKey() : $this->getKey().$this->routeKeySeparator().$slug;
    }

    /**
     * „/” dla stron (12/jan-kowalski); w parametrach zapytania (np. ?rozgrywki=) czytelniejszy jest „-”.
     */
    protected function routeKeySeparator(): string
    {
        return '/';
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
