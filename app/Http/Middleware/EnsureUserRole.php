<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role?->name !== $role) {
            Log::warning('Role middleware denied access.', [
                'required_role' => $role,
                'user_id' => $request->user()?->id,
                'actual_role' => $request->user()?->role?->name,
                'path' => $request->path(),
                'url' => $request->fullUrl(),
            ]);

            abort(403);
        }

        return $next($request);
    }
}
