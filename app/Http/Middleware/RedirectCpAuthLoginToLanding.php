<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCpAuthLoginToLanding
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get') && $request->path() === 'cp/auth/login') {
            return redirect('/');
        }

        return $next($request);
    }
}
