<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slug
{
    /**
     * Czytelny, unikalny adres: „seniorzy”, a przy powtórce „seniorzy-2”.
     *
     * @param  class-string<Model>  $model
     */
    public static function unique(string $model, string $text, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($text) ?: 'strona';
        $slug = $base;
        $suffix = 2;

        while ($model::query()->where($column, $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
