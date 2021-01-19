<?php

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use ShopEM\Http\Requests\Platform\SaveLiveRoomRequest;
use ShopEM\Services\WechatService;
use ShopEM\Models\WechatLive;

class WechatLiveController extends BaseController
{

	public function lists(Request $request)
	{
        $users = \ShopEM\Models\UserRelYitianInfo::whereNull('yitian_id')->paginate(50);
        foreach ($users as $key => $user) 
        {
            dd($user['yitian_id']);
        }
		// $service = new  \ShopEM\Services\YitianGroupServices();
  //       $data = $service->memberInfo('13710550084');
  //       dd($data);
        echo "string";
	}



	/**
	 * [saveLiveRoom 保存]
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
    public function saveLiveRoom(SaveLiveRoomRequest $request)
    {
    	$data = $request->only('roomid', 'name', '', '','');
    	$live = WechatLive::find($id);
    	if (empty($live)) {
            return $this->resFailed(701);
        }
    }


    public function updateStatus(Request $request)
    {
        $request = $request->only('status', 'id');
        $id = $request['id'];
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        if ($request['status'] == "open") {
            $update_data['room_status'] = '1';
            $msg = "开启";
        } else {
            $update_data['room_status'] = '0';
            $msg = "关闭";
        }
        $live = WechatLive::find($id);
        if (empty($live)) {
            return $this->resFailed(701);
        }
        $msg_text = $live['name'] . "直播房间" . $msg;

        DB::beginTransaction();
        try
        {
            //修改店铺状态为关闭
            $live->update($update_data);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }

	/**
	 * [pullList 获取直播房间列表]
	 * @param  WechatService $service [description]
	 * @return [type]                 [description]
	 */
    public function pullList(WechatService $service)
    {
    	try {
    		$access_token = $service->getAccessToken();
    		$data = $this->getliveinfo($access_token);
    		
    	} catch (\Exception $e) {
    		return $this->resFailed(406,$e->getMessage());
    	}
    	return $this->resSuccess($data);
    }


    /**
     * @Author nlx
     * @param $code
     * @param $appid
     * @param $appsecret
     * @return bool|mixed
     * @throws \Exception
     */
    private function getliveinfo($access_token,$start=0, $limit=10 )
    {
        try {
            //获取session_key和openid
            $client = new \GuzzleHttp\Client();
            $api_url = 'http://api.weixin.qq.com/wxa/business/getliveinfo';
            $api_url = $api_url . '?access_token=' . $access_token;

            $request['start'] = $start;
            $request['limit'] = $limit;
            $respond = $client->request('POST', $api_url, ['json' => $request]);
            if ($respond->getStatusCode() === 200) {
                $jscode_res = json_decode($respond->getBody()->getContents(), true);
                if (isset($jscode_res['errcode']) && $jscode_res['errcode'] !== 0) {
                    throw new \Exception($jscode_res['errmsg']);
                }
                return $jscode_res;
            }
            throw new \Exception('请求失败');
        } catch (\Exception $exception) {
            throw new \Exception('无法获取：' . $exception->getMessage());
        }
    }

}
