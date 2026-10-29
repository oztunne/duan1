<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class SiteLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale') ?? $request->session()->get('locale', 'vi');
        if (! in_array($locale, ['vi', 'ja'], true)) {
            $locale = 'vi';
        }
        App::setLocale($locale);
        URL::defaults(['locale' => $locale]);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
