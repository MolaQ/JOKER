<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Player;
use App\Models\User;
use App\Notifications\ClubAccountCreated;
use App\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Konta zakładane przez klub: zawodnik albo rodzic dostaje konto po wpisaniu jego e-maila
 * (w panelu, w imporcie albo po zatwierdzeniu propozycji rodzica) i link do ustawienia hasła.
 */
class ClubAccounts
{
    /**
     * Łączy zawodnika z kontem o tym e-mailu; gdy konta nie ma, zakłada je z rolą „Zawodnik”.
     */
    public static function connectPlayer(Player $player, string $email): User
    {
        $email = Str::lower(trim($email));
        $current = $player->user;

        // Konto z adresem zastępczym (bez e-maila) dostaje prawdziwy adres zamiast nowego konta.
        if ($current && $current->email !== $email && str_ends_with($current->email, '@brak-emaila.jokerpila.pl') && ! User::where('email', $email)->exists()) {
            $current->update(['email' => $email]);
            self::sendWelcome($current);

            return $current;
        }

        $user = self::findOrCreate($email, $player->full_name, UserRole::Player);
        $player->update(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Dopisuje rodzica do zawodnika (zawodnik może mieć kilku rodziców); brakujące konto zakłada z rolą „Rodzic”.
     */
    public static function connectGuardian(Player $player, string $email, ?string $name = null): User
    {
        $user = self::findOrCreate(Str::lower(trim($email)), $name ?: 'Rodzic: '.$player->full_name, UserRole::Parent);
        $player->guardians()->syncWithoutDetaching([$user->id]);

        return $user;
    }

    /**
     * Wpis „Anna Nowak <anna@example.com>” albo sam e-mail; kilka rozdzielonych przecinkiem lub średnikiem.
     *
     * @return list<array{email: string, name: ?string}>
     */
    public static function parseContacts(string $value): array
    {
        return collect(preg_split('/[,;]/', $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->map(function (string $item) {
                if (preg_match('/^(.*)<([^>]+)>$/', $item, $match)) {
                    return ['email' => trim($match[2]), 'name' => trim($match[1], " \"'") ?: null];
                }

                return ['email' => $item, 'name' => null];
            })
            ->values()
            ->all();
    }

    private static function findOrCreate(string $email, string $name, UserRole $role): User
    {
        if ($user = User::firstWhere('email', $email)) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            return $user;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'is_active' => false,
            'role' => $role,
        ]);
        $user->activate();
        AuditLog::record('account.created', 'Klub założył konto '.$user->name.' ('.$role->label().')', $user);
        self::sendWelcome($user);

        return $user;
    }

    private static function sendWelcome(User $user): void
    {
        $user->notify(new ClubAccountCreated(Password::broker()->createToken($user)));
    }
}
