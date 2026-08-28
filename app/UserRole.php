<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case Trainer = 'trainer';
    case Parent = 'parent';
    case Player = 'player';
    case Fan = 'fan';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Trainer => 'Trener',
            self::Parent => 'Rodzic',
            self::Player => 'Zawodnik',
            self::Fan => 'Kibic',
            self::Guest => 'Gość',
        };
    }

    public function canManageContent(): bool
    {
        return in_array($this, [self::Admin, self::Trainer]);
    }

    public function canComment(): bool
    {
        return $this !== self::Guest;
    }
}
