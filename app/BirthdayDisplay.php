<?php

namespace App;

/**
 * Jak pokazać urodziny osoby w kalendarzu meczów.
 */
enum BirthdayDisplay: string
{
    case Hidden = 'hidden';
    case Cake = 'cake';
    case Age = 'age';

    public function label(): string
    {
        return match ($this) {
            self::Hidden => 'Nie pokazuj',
            self::Cake => 'Sam torcik (bez wieku)',
            self::Age => 'Torcik z wiekiem',
        };
    }
}
