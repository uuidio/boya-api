<?php

namespace ShopEM\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use ShopEM\Models\LiveUsers;

class LiveUserAccountTime
{
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
        \Log::info('$user_id:' . $user_id);

        if ($user) {
            if ($user->account_end_time < time()) {
                return response("您的账号已过期，请续费", 406);
            }
        }


        return $next($request);
    }
}
