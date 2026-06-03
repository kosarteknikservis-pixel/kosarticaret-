<?php

namespace App\Http\Middleware;

use App\Services\AnalyticsTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackStorefrontVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isSuccessful()) {
            $tracker = app(AnalyticsTracker::class);

            if ($tracker->shouldTrackRequest($request)) {
                $tracker->trackPageView($request);
            }
        }

        return $response;
    }
}
