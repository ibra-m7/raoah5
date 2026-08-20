<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class ConvertValidationNotifications
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            $errors = $request->session()->get('errors');
            if ($errors instanceof ViewErrorBag && $errors->any()) {
                flash()->error((string) $errors->first());
            }
        }

        return $next($request);
    }
}
