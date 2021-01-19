<?php

namespace ShopEM\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class TradeCreatelimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if ($request->path() == "shop/v1/trade/create") {

            $user_id = Auth::guard('api')->user()->id;
            $service = new   \ShopEM\Services\SecKillService();
            $order = $service->isActionAllowed($user_id, "create_order", 2 * 1000, 1);
            if ($order) {
                return $next($request);
            }else{
                return response("请求过快！",400);
            }
        } else {

            return $next($request);
        }

    }
}
