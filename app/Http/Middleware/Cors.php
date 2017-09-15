<?php

namespace App\Http\Middleware;

use Closure;
use Log;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        exit;
        Log::info('cccccccccccc');
        return $next($request);
    }
}
