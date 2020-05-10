<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Session;

class RedirectIfLaboratoire
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (sizeof(Auth::user()->roles) >0 && "Laboratoire" != Auth::user()->roles[0]['nom']) {
            return $next($request);
        }

        return redirect('produits');
    }
}
