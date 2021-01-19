<?php

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Models\Message;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;

class MessageController extends BaseController
{
    public function lists()
    {
        $lists = Message::orderBy('id','desc')->paginate(10);
        return $this->resSuccess([
            'lists' => $lists,
            'field' => [
                ['key' => 'id','dataIndex' => 'id', 'title' => 'ID'],
                ['key' => 'content','dataIndex' => 'content', 'title' => '留言内容'],
                ['key' => 'created_at','dataIndex' => 'created_at', 'title' => '留言时间'],
            ],
        ]);
    }
}
