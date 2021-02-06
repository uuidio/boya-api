<?php

namespace ShopEM\Services\WebSocket;

use Illuminate\Support\Facades\Redis;
use Predis\Client as Predis;
use Swoole\Process;
use Swoole\WebSocket\Server;

class UserLogin
{
    protected $host = '0.0.0.0';
    protected $port = 9501;

    public $ws = null;

    public function __construct()
    {
        $this->ws = new Server($this->host, $this->port);
        $this->ws->on('open', [$this, 'onOpen']);
        $this->ws->on('message', [$this, 'onMessage']);
        $this->ws->on('close', [$this, 'onClose']);
        $this->ws->addProcess($this->pushUserLogoutProcess($this->ws));
        $this->ws->set([
            //心跳检测
            'heartbeat_check_interval' => 60,
            'heartbeat_idle_time' => 120,
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
//                $user = Cache::get('user');
//                if ($user == 1) {
//                    $content = date('YmdHis');
//                    foreach ($this->ws->connections as $fd) {
//                        $this->ws->push($fd, $content);
//                    }
//
//                    Cache::put('user', 2);
//                }

                sleep(10);
            }
        }, false, 2, 1);

        return $process;
    }

    public function onRequest($request, $response)
    {
        \Log::info([
            '$request->get' => $request->get,
        ]);

        // 接收http请求从get获取message参数的值，给用户推送
        // $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
        foreach ($this->ws->connections as $fd) {
            // 需要先判断是否是正确的websocket连接，否则有可能会push失败
            if ($this->ws->isEstablished($fd)) {
                $this->ws->push($fd, $request->get['message']);
            }
        }
    }

    /**
     * 监听WebSocket连接打开事件
     *
     * @param Server $ws
     * @param $request
     */
    public function onOpen(Server $ws, $request)
    {
        $redis = Redis::connection('userloginlog');

        \Log::info([
            'fd' => $request->fd,
            'server' => $request->server,
            'get' => $request->get,
        ]);

        $fd = $request->fd;
        $uid = $request->get['uid'];
        $token = md5($request->get['token']);

        $key = $uid . '-fd';
        $val = $token . ':' . $fd;

        $redis::lpush($key, $val);

        $ws->push($request->fd, "hello1, welcome\n" . $request->fd);
    }

    /**
     * 监听WebSocket消息事件
     *
     * @param Server $ws
     * @param $frame
     */
    public function onMessage(Server $ws, $frame)
    {
        echo "Message: {$frame->data}\n";

        $ws->push($frame->fd, $frame->fd . "server: {$frame->data}");
    }

    /**
     * 监听WebSocket连接关闭事件
     *
     * @param Server $ws
     * @param $fd
     */
    public function onClose(Server $ws, $fd)
    {
        \Log::info($fd . ':关闭链接');
        echo "client-{$fd} is closed\n";
    }

    public function pushUserLogout($content)
    {
        foreach ($this->ws->connections as $fd) {
            $this->ws->push($fd, $content);
        }
    }
}