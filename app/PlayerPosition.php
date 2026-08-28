<?php

namespace App;

enum PlayerPosition: string
{
    case Setter = 'setter';
    case OutsideHitter = 'outside_hitter';
    case Opposite = 'opposite';
    case MiddleBlocker = 'middle_blocker';
    case Libero = 'libero';

    public function label(): string
    {
        return match ($this) {
            self::Setter => 'Rozgrywający',
            self::OutsideHitter => 'Przyjmujący',
            self::Opposite => 'Atakujący',
            self::MiddleBlocker => 'Środkowy',
            self::Libero => 'Libero',
        };
    }
}
