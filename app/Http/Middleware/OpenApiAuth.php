<?php

namespace ShopEM\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use ShopEM\Traits\ApiResponse;

class OpenApiAuth
{
    use ApiResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth = $request->header('Authorization');
        if (!Cache::has('open_api_'.$auth)) return $this->resFailed(403, 'token失效，请重新获取');
        $info = json_decode(Cache::get('open_api_'.$auth),true);
        if ($info['api_auth'] !== 'all') {
            $api_auth = explode(',',$info['api_auth']);
            $map = config('openapi');
            $key = array_search($request->path(),$map);
            if (!in_array($key, $api_auth)) {
                return $this->resFailed(402, '没有权限');
            }
        }
        return $next($request);
    }
}
