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

    public function description(): string
    {
        return match ($this) {
            self::Setter => 'Mózg zespołu. Rozgrywający ustawia piłkę do ataku i decyduje, kto skończy akcję.',
            self::OutsideHitter => 'Wszechstronny zawodnik pierwszej linii, kluczowy zarówno w przyjęciu zagrywki, jak i w ataku.',
            self::Opposite => 'Główny snajper zespołu. Atakujący przede wszystkim zdobywa punkty, także z drugiej linii.',
            self::MiddleBlocker => 'Specjalista od bloku i szybkich ataków ze środka siatki.',
            self::Libero => 'Lider obrony i przyjęcia. Gra w koszulce innego koloru i nie atakuje ponad siatką.',
        };
    }
}
