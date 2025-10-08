<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle($request, Closure $next)
    {
        $timeout = config('session.lifetime') * 60; // convert to seconds
        $lastActivity = $request->session()->get('last_activity');

        if ($lastActivity && time() - $lastActivity > $timeout) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('message', 'Your session has expired.');
        }

        $request->session()->put('last_activity', time());

        return $next($request);
    }
}
