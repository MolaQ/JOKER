<?php

namespace App;

use App\Support\SiteSettings;

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

    /**
     * Opis do dymka po kliknięciu pozycji (Ustawienia → Profile zawodników).
     * Po angielsku zostaje tłumaczenie, dopóki klub nie zmieni domyślnego tekstu.
     */
    public function description(): string
    {
        $key = 'position_'.$this->value;
        $default = (string) SiteSettings::fields()[$key]['default'];
        $custom = trim((string) app(SiteSettings::class)->get($key));

        return $custom === '' || $custom === $default ? __($default) : $custom;
    }
}
