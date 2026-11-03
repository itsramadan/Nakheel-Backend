<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * بيتأكد إن التوكن المستخدم راجع لحساب "مهندس" (App\Models\User) أو
 * "مزارع" (App\Models\Client) على حسب الراوت. لازم يجي بعد auth:sanctum.
 *
 * الاستخدام: ->middleware('role:engineer') أو ->middleware('role:farmer')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        $isEngineer = $user instanceof User;
        $isFarmer = $user instanceof Client;

        if (($role === 'engineer' && ! $isEngineer) || ($role === 'farmer' && ! $isFarmer)) {
            abort(403, 'غير مسموح لهذا الحساب بالوصول لهذا المسار.');
        }

        return $next($request);
    }
}
