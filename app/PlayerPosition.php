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
            self::Setter => __('Rozgrywający'),
            self::OutsideHitter => __('Przyjmujący'),
            self::Opposite => __('Atakujący'),
            self::MiddleBlocker => __('Środkowy'),
            self::Libero => __('Libero'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Setter => __('Mózg zespołu. Rozgrywający ustawia piłkę do ataku i decyduje, kto skończy akcję.'),
            self::OutsideHitter => __('Wszechstronny zawodnik pierwszej linii, kluczowy zarówno w przyjęciu zagrywki, jak i w ataku.'),
            self::Opposite => __('Główny snajper zespołu. Atakujący przede wszystkim zdobywa punkty, także z drugiej linii.'),
            self::MiddleBlocker => __('Specjalista od bloku i szybkich ataków ze środka siatki.'),
            self::Libero => __('Lider obrony i przyjęcia. Gra w koszulce innego koloru i nie atakuje ponad siatką.'),
        };
    }
}
