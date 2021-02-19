<?php

namespace ShopEM\Http\Middleware;

use Closure;

class LiveUserAccountTime
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

        \Log::info('LiveUserAccountTime');

        return $next($request);
    }
}
