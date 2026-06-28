<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * 許可するroleを引数で指定する（例: ->middleware('role:admin,hq_staff')）。
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $this->resolveRoles($allowedRoles), true)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $allowedRoles
     * @return array<int, UserRole>
     */
    private function resolveRoles(array $allowedRoles): array
    {
        return array_map(
            fn (string $role) => UserRole::from($role),
            $allowedRoles
        );
    }
}
