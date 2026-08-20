<?php

namespace App\Http\Middleware;

use App\Models\Courier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCourier
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Courier) {
            abort(403, 'هذا التطبيق للموصلين فقط.');
        }

        if (! $user->is_active) {
            abort(403, 'حسابك غير مفعّل. تواصل مع الإدارة.');
        }

        return $next($request);
    }
}
