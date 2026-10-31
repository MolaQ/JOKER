<?php

namespace App\Models\Concerns;

/**
 * Opcjonalna angielska wersja treści (kolumny z przyrostkiem _en). Gdy strona jest po angielsku
 * i tłumaczenie istnieje, pokazujemy je; w przeciwnym razie wersję polską.
 */
trait HasEnglishVersion
{
    /**
     * Czy wpis ma angielską wersję (wystarczy angielski tytuł; przy wpisie widać wtedy brytyjską flagę).
     * Puste angielskie pola (np. zajawka) zastępujemy polskimi.
     */
    public function hasEnglish(): bool
    {
        return filled($this->title_en);
    }

    /**
     * Wartość pola w bieżącym języku strony (angielska tylko, gdy wpis ma tłumaczenie).
     */
    public function localized(string $attribute): ?string
    {
        if (app()->getLocale() === 'en' && filled(strip_tags((string) $this->{$attribute.'_en'}))) {
            return $this->{$attribute.'_en'};
        }

        return $this->{$attribute};
    }

    public function localizedTitle(): string
    {
        return (string) $this->localized('title');
    }

    public function localizedContent(): ?string
    {
        return $this->localized('content');
    }
}
