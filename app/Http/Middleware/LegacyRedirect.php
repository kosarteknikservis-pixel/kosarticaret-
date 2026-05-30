<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->path(), '/');
        $map = config('redirects', []);

        if ($path !== '/' && isset($map[$path])) {
            $target = $map[$path];
            $url = str_starts_with($target, 'http')
                ? $target
                : url('/'.ltrim($target, '/'));

            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}
