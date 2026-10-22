<?php

namespace App;

/**
 * Stan konta widoczny dla użytkownika i w panelu.
 */
enum AccountStatus: string
{
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Active = 'active';
    case Suspended = 'suspended';
    case Disabled = 'disabled';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Czeka na aktywację',
            self::Rejected => 'Prośba odrzucona',
            self::Active => 'Aktywne',
            self::Suspended => 'Zawieszone przez klub',
            self::Disabled => 'Wyłączone przez użytkownika',
            self::Deleted => 'Usunięte',
        };
    }

    /**
     * Klasy plakietki w panelu.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            self::Rejected => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Active => 'bg-green-50 text-green-700 ring-green-600/20',
            self::Suspended => 'bg-zinc-100 text-zinc-700 ring-zinc-500/20',
            self::Disabled => 'bg-zinc-100 text-zinc-700 ring-zinc-500/20',
            self::Deleted => 'bg-zinc-100 text-zinc-500 ring-zinc-400/20',
        };
    }
}
