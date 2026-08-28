<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            abort(403, 'Musisz być zalogowany, aby uzyskać dostęp do tej strony.');
        }

        $allowedRoles = array_map(fn ($role) => UserRole::from($role), $roles);

        if (! $request->user()->hasAnyRole($allowedRoles)) {
            abort(403, 'Nie masz uprawnień do dostępu do tej strony.');
        }

        return $next($request);
    }
}
