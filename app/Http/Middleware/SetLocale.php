<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale') === 'en' || $request->segment(1) === 'en' ? 'en' : 'bn';
        app()->setLocale($locale);
        view()->share('locale', $locale);
        return $next($request);
    }
}
