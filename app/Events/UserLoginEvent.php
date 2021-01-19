<?php

namespace ShopEM\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use ShopEM\Models\UserLoginLog;

class UserLoginEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn($userId, $loginName,$login_platform='',$loginWay=null)
    {
        $params['user_id'] = $userId;
        $params['username'] = $loginName;
        $params['login_ip'] = request()->getClientIp();
        $params['login_platform'] = $login_platform ? $login_platform : 'H5';
        $params['login_way'] = $loginWay ? $loginWay."信任登录" : "账号登录";

        return  UserLoginLog::create($params);
    }

}
