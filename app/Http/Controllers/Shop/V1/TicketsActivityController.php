<?php

/**
 * @Author: nlx
 * @Date:   2019-07-30 18:45:17
 */
namespace ShopEM\Http\Controllers\Shop\V1;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ShopEM\Services\CmsPushService;
use ShopEM\Http\Controllers\Shop\BaseController;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TicketsActivityController extends BaseController
{

	/**
     * [activeCodeDetail 发行的卡券兑换码详情]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function activeCodeDetail(Request $request,CmsPushService $repository)
    {
    	$input = $request->all();
    	$result = $repository->activityCode($input);
    	if (isset($result['code'])) {
    		$result['ecode'] = $result['code'];
    	}
    	return $result;
    }

    //人工核销操作
    public function handApply(Request $request,CmsPushService $repository)
    {
    	$input = $request->all();
    	$result = $repository->activityHand($input);
    	if (isset($result['code'])) {
    		$result['ecode'] = $result['code'];
    	}

    	return $result;
    }


    public function checkpass(Request $request)
    {
        $input = $request->only('passwork');
        if (!isset($input['passwork']) || !$input['passwork']) {
            return $this->resFailed(414,'请输入访问密码');
        }
        $passwork = $input['passwork'];
        $table_pass = config('passwork.applytable');
        if (md5($passwork) != $table_pass) {
            return $this->resFailed(414,'访问密码错误');
        }
        $mdToken = md5($table_pass.time());
        Cache::put('api_table_apply_token', $mdToken, Carbon::now()->addMinutes(60));
        return $this->resSuccess(['msg'=>'访问成功','token'=>$mdToken]);
    }
    //暂时作为核销报表，
    //后期需要改版
    public function applyTable(Request $request,CmsPushService $repository)
    {

        // dd(Paginator::resolveCurrentPath());
        $input = $request->all();
        $token = Cache::get('api_table_apply_token',md5('###'.time()));
        if (!isset($input['token']) || !$input['token'] || $input['token'] != $token) {
            return $this->resFailed(506,'访问密码失效');
        }
        
        $result = $repository->cmsApiData($input,'applyTable');
        if (isset($result['code'])) {
            $result['ecode'] = $result['code'];
        }
        // $result = json_encode($result,1);
        $path = Paginator::resolveCurrentPath();
        $lists = $result['result']['lists'];
        
        $result['result']['lists']['path'] =  $path;
        $result['result']['lists']['first_page_url'] = str_replace($lists['path'], $path, $lists['first_page_url']);
        $result['result']['lists']['last_page_url']  = str_replace($lists['path'], $path, $lists['last_page_url']);
        $result['result']['lists']['next_page_url']  = str_replace($lists['path'], $path, $lists['next_page_url']);
        return $result;
    }


    public function filterExport(Request $request,CmsPushService $repository)
    {
        $input = $request->all();
        $token = Cache::get('api_table_apply_token',md5('###'.time()));
        if (!isset($input['token']) || !$input['token'] || $input['token'] != $token) {
            return $this->resFailed(506,'访问密码失效');
        }

        $result = $repository->cmsApiData($input,'applyFilterExport');
        if (isset($result['code'])) {
            $result['ecode'] = $result['code'];
        }
        return $result;
    }


    /**
     * [activityCollect 用于活动记录数据使用]
     * @param string $value [description]
     */
    public function activityCollect(Request $request)
    {
        $data = $request->only('time', 'action', 'url');
        $check = checkInput($data,'collectData','activity');
        if($check['code']){
            return $this->resFailed(414,$check['msg']);
        }

        //判断值是否为时间戳
        $time = strtotime($data['time']);
        if(strtotime(date('Y-m-d H:i:s',$time)) === $time) 
        {
            $timestamp = strtotime($data['time']);
        } 
        else
        {
            $timestamp = $data['time'];
        }
        // 1,访问时间传过来的不能超过5秒
        // 2,使用ip作为缓存防止被刷
        $differ_time = time()-$timestamp;
        if ( $differ_time > 5 || $differ_time < -5 ) 
        {
            return $this->resFailed(414,'与当前时间相差5秒');
        }

        $mdl_collect = new \ShopEM\Models\ActivityCollect;
        try {
            $mdl_collect::newSave($data);
        } catch (Exception $e) {
            return $this->resFailed(406,'网络出错');
        }

        return $this->resSuccess();
    }

}