<?php

namespace App\Models\Concerns;

/**
 * Licznik wyświetleń profilu: jedno wejście na sesję, bez zmiany daty modyfikacji rekordu.
 */
trait CountsViews
{
    public function registerView(): void
    {
        $key = 'viewed.'.$this->getTable().'.'.$this->getKey();

        if (session()->has($key)) {
            return;
        }

        session()->put($key, true);
        static::whereKey($this->getKey())->toBase()->increment('views_count');
        $this->views_count = (int) $this->views_count + 1;
        $this->syncOriginalAttribute('views_count');
    }

    /**
     * „1 wyświetlenie”, „3 wyświetlenia”, „1 250 wyświetleń” (po angielsku „1 view”, „1,250 views”).
     */
    public function viewsLabel(): string
    {
        $count = (int) $this->views_count;

        if (app()->getLocale() === 'en') {
            return number_format($count).' '.($count === 1 ? 'view' : 'views');
        }

        $word = match (true) {
            $count === 1 => 'wyświetlenie',
            $count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 10 || $count % 100 >= 20) => 'wyświetlenia',
            default => 'wyświetleń',
        };

        return number_format($count, 0, ',', ' ').' '.$word;
    }
}
