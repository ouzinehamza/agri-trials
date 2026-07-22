<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Only the company Admin may manage configuration, users, branding, and workspaces (SPEC §5). */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Réservé à l\'administrateur.');

        return $next($request);
    }
}
