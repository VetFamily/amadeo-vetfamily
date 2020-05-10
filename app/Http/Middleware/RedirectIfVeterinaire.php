<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;

class RedirectIfVeterinaire
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
        if (sizeof(Auth::user()->roles) >0 && "Vétérinaire" != Auth::user()->roles[0]['nom']) {
            return $next($request);
        }

        return redirect('/');
    }
}
