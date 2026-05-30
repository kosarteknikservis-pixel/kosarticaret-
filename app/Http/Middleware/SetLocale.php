<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('kosar.locales', ['tr', 'en']);
        if ($request->has('lang') && in_array($request->query('lang'), $allowed, true)) {
            session(['locale' => $request->query('lang')]);
        }

        $locale = session('locale', config('kosar.default_locale', 'tr'));

        if (! in_array($locale, $allowed, true)) {
            $locale = config('kosar.default_locale', 'tr');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
