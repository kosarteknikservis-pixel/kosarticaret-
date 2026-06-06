<?php

namespace App\Http\Middleware;

use App\Support\LegacyRedirectResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = '/'.trim($request->path(), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $target = LegacyRedirectResolver::resolve($request);

        if ($target === null) {
            $map = config('redirects', []);
            if (isset($map[$path])) {
                $target = (string) $map[$path];
            }
        }

        if ($target !== null) {
            $url = str_starts_with($target, 'http')
                ? $target
                : url($target === '/' ? '/' : '/'.ltrim($target, '/'));

            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}
