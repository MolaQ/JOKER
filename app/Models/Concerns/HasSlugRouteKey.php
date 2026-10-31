<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Adres po kolumnie slug („/druzyny/seniorzy”). Stare adresy z numerem nadal działają.
 */
trait HasSlugRouteKey
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()->where($field ?? 'slug', $value)->first()
            ?? ($field === null && ctype_digit((string) $value) ? $this->newQuery()->find((int) $value) : null);
    }
}
