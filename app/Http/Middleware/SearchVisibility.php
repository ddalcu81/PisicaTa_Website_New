<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchVisibility
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('pisicata.indexable') || $request->is('admin', 'admin/*', 'cauta', 'login')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
