<?php

namespace ShopEM\Services\WebSocket;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\Process;
use Swoole\WebSocket\Server;

class UserLogin
{
    protected $host = '0.0.0.0';
    protected $port = 9501;

    const CODE_DEFAULT = 0; //默认
    const CODE_SIGN_OUT = 1; //退出登录

    public $ws = null;

    public function __construct()
    {
        $this->ws = new Server($this->host, $this->port);
        $this->ws->on('open', [$this, 'onOpen']);
        $this->ws->on('message', [$this, 'onMessage']);
        $this->ws->on('close', [$this, 'onClose']);
        //$this->ws->addProcess($this->pushUserLogoutProcess($this->ws));
        $this->ws->set([
            //心跳检测
            'heartbeat_check_interval' => 60, //每60秒遍历一次
            'heartbeat_idle_time' => 600, // 连接如果600秒内未向服务器发送任何数据，此连接将被强制关闭
        ]);

        $this->ws->start();
    }

    /**
     * 推送用户被迫下线消息进程
     *before
     * @param $ws
     * @return Process
     */
    public function pushUserLogoutProcess($ws)
    {
        $process = new Process(function () use ($ws) {
            while (true) {

                sleep(1);
            }
        }, false, 2, 1);

        return $process;
    }

    /**
     * 监听WebSocket连接打开事件
     *
     * @param Server $ws
     * @param $request
     */
    public function onOpen(Server $ws, $request)
    {
        $ws->push($request->fd, $this->contents(UserLogin::CODE_DEFAULT, '链接成功'));

        try {
            $redis = Redis::connection('user_login_log');
            $fd = $request->fd;
            $uid = $request->get['uid'];
            $token = $request->get['token'];
            $key = $uid . '_fd';
            $val = $fd . ':' . $token;
            //获取最后一次登录的token
            $last = $redis->lindex($key, 0);

            if ($last) {
                list($u_fd, $u_token) = explode(':', $last);
                if (strnatcmp($token, $u_token) && $this->ws->isEstablished($u_fd) && $this->ws->exist($u_fd)) {
                    //通知 $u_token 下线
                    $ws->push($u_fd, $this->contents(UserLogin::CODE_SIGN_OUT, '您的账号已在其他设备登录'));
                }
            }

            $redis->lpush($key, [$val]);
            $redis->ltrim($key,0,4);

        } catch (\Exception $e) {
            Log::info('用户登录ws通信异常 :' . $e->getMessage());
            $ws->push($request->fd, $this->contents(UserLogin::CODE_DEFAULT, '服务器异常'));
        }

    }

    /**
     * 监听WebSocket消息事件
     *
     * @param Server $ws
     * @param $frame
     */
    public function onMessage(Server $ws, $frame)
    {

        Log::info($frame->fd . "message : {$frame->data}");

        $ws->push($frame->fd, $this->contents(UserLogin::CODE_DEFAULT, '服务端成功接收消息'));
    }


    /**
     * @param int $code
     * @param string $msg
     * @return false|string
     */
    public function contents(int $code, string $msg)
    {
        $content = [
            'code' => $code,
            'msg' => $msg
        ];

        return json_encode($content, JSON_UNESCAPED_UNICODE);

    }

    /**
     * 监听WebSocket连接关闭事件
     *
     * @param Server $ws
     * @param $fd
     */
    public function onClose(Server $ws, $fd)
    {
        Log::info($fd . ':关闭链接');
        $ws->push($fd, $this->contents(UserLogin::CODE_DEFAULT, '关闭链接'));
    }
}