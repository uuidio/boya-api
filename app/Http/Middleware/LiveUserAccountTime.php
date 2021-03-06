<?php

namespace ShopEM\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use ShopEM\Models\LiveUsers;
use ShopEM\Traits\ApiResponse;

class LiveUserAccountTime
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user_id = Auth::guard('live_users')->user()->id;
        $user = LiveUsers::where('id', $user_id)->select('id', 'account_end_time')->first();

        if ($user) {
            if ($user->account_end_time < time()) {
                return $this->resFailed(403, '您的账号已过期，请续费');
            }
        }


        return $next($request);
    }
}
