<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\PermissionOverride;
use App\Models\RoleAssignment;
use Illuminate\Console\Command;

/**
 * Usuwa role i wyjątki w uprawnieniach, którym minęła data „ważne do”, i zapisuje to w dzienniku zmian.
 * Wygasła rola przestaje działać od razu (konto jej nie widzi), komenda tylko sprząta i zostawia ślad.
 */
class ExpireRoles extends Command
{
    protected $signature = 'joker:expire-roles';

    protected $description = 'Usuń role i uprawnienia, którym minęła data „ważne do”';

    public function handle(): int
    {
        RoleAssignment::expired()->with('user')->get()->each(function (RoleAssignment $assignment) {
            if ($assignment->user) {
                AuditLog::record('roles.expired', 'Wygasła rola '.$assignment->role->label().' konta '.$assignment->user->name, $assignment->user, [
                    'odebrane' => [$assignment->role->label()],
                    'ważne do' => $assignment->expires_at->format('d.m.Y'),
                ]);
            }

            RoleAssignment::where('user_id', $assignment->user_id)->where('role', $assignment->role)->delete();
        });

        PermissionOverride::expired()->with('user')->get()->each(function (PermissionOverride $override) {
            if ($override->user) {
                AuditLog::record('permissions.expired', 'Wygasło '.($override->allowed ? 'nadane' : 'odebrane').' „'.$override->permission->label().'” konta '.$override->user->name, $override->user, [
                    'uprawnienie' => $override->permission->value,
                    'ważne do' => $override->expires_at->format('d.m.Y'),
                ]);
            }

            $override->delete();
        });

        return self::SUCCESS;
    }
}
