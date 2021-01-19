<?php
/**
 * @Filename        IndexController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\UserRelShopInfo;
use ShopEM\Repositories\SiteConfigRepository;
use ShopEM\Repositories\RecommendConfigRepository;
use ShopEM\Repositories\ConfigRepository;
use ShopEM\Repositories\GmPlatformRepository;
use ShopEM\Repositories\ShopRepository;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\UserRelYitianInfo;
use EasyWeChat;
use ShopEM\Services\ShopsService;
use ShopEM\Http\Requests\Shop\ShopNearbyRequest;


class IndexController extends BaseController
{


    /**
     * 首页详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param SiteConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(SiteConfigRepository $repository)
    {
        return $this->resSuccess($repository->configItems('index',$this->GMID));
    }


    /**
     *  获取首页挂件
     * @Author hfh_wind
     * @param SiteConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function IndexWidgets(Request $request,SiteConfigRepository $repository)
    {
        if(!isset($request->page)){
            return $this->resFailed(500,'参数错误');
        }
        $page = $request->page;
        $custom_id = $request->custom_id??0;
        if ($custom_id > 0) {
            $gm_id = 0;
        }
        return $this->resSuccess($repository->configItems_v1($page,$custom_id,$gm_id,false));
    }

    /**
     * [newIndexWidgets 首页挂件升级]
     * @param  Request          $request    [description]
     * @param  ConfigRepository $repository [description]
     * @return [type]                       [description]
     */
    public function newIndexWidgets(Request $request,ConfigRepository $repository,ShopsService $service)
    {
        if(!isset($request->page)){
            return $this->resFailed(500,'参数错误');
        }
        $page = $request->page;
        if($page == 'index_fit'){
            $service->addPageView($request->getClientIp(),$request->path(),$page,$this->GMID);
        }
        return $this->resSuccess($repository->configItems_index($request->page,$this->GMID));
    }

    /**
     * 图片弹窗
     *
     * @Author djw
     * @param ConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function imagePop(ConfigRepository $repository)
    {
        return $this->resSuccess($repository->configItem('index', 'pop', $this->GMID));
    }

    /**
     * 读取banner图配置
     *
     * @Author djw
     * @param ConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function banner(ConfigRepository $repository)
    {
        $config = $repository->configItem('shop', 'banner', $this->GMID);
        $respon['user_center_bottom_image'] = $config['user_center_bottom_image']['value'] ?? [];
        $respon['point_banner'] = $config['point_banner']['value'] ?? [];
        $respon['groups_banner'] = $config['groups_banner']['value'] ?? [];
        return $this->resSuccess($respon);
    }

    /**
     * 读取banner图配置
     *
     * @Author djw
     * @param ConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function csmobile(Request $request,ConfigRepository $repository)
    {
        $gm_id = $request->gm_id??$this->GMID;
        $config = $repository->configItem('shop', 'base', $gm_id);
        $respon['mobile'] = $config['shop_cs_mobile']['value'] ?? 0;
        return $this->resSuccess($respon);
    }


    /**
     * 获取客服微信
     *
     * @Author nlx
     * @param ConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function csweixin(Request $request,ConfigRepository $repository)
    {
        $gm_id = $request->gm_id??$this->GMID;
        $config = $repository->configItem('shop', 'base', $gm_id);
        $respon['weixin'] = $config['shop_cs_weixin']['value'] ?? '';
        return $this->resSuccess($respon);
    }

    /**
     * 示例
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function test()
    {
        $d = [
            'addr'=>['state'=>'广东省','city'=>'广州市','district'=>'天河区','address'=>'元岗路616号A8-1栋三楼'],
            'items'=>[['lhy_id'=>'1883232','num'=>'1']],
        ];
        $TradeService = new \ShopEM\Services\TradeService;
        try{
            $aa = $TradeService->confirmReceipt('30200225160749193124');
        }catch (\Exception $exception) {
            return $this->resSuccess($exception->getMessage());
//            testLog('自动收货:'. $exception->getMessage());
            return false;
        }
        return $this->resSuccess($aa);
        dd(oldHySign($d));
        $goods_model = new \ShopEM\Models\Goods();
        $old = $goods_model->whereIn('id',['434','435'])->get()->toArray();
        $a = $old[0]['third_attr_update'];
        return $this->resSuccess($a);
        dd($old[0]['third_attr_update']);
        return $this->resSuccess([], '这是测试!!');
        return $this->resSuccess(json_decode($i,true), '这是商城管理模块');
    }

    /**
     * [cleanOneCache 清理某个会员账户的服务器缓存]
     * @Author mssjxzw
     * @param  string  $id [description]
     * @return [type]      [description]
     */
    public function cleanOneCache($id ='')
    {
        if (!$id || !is_numeric($id)) {
            return $this->resFailed(414,'无效参数');
        }
        $user = \ShopEM\Models\UserAccount::where('id',$id)->first();
        if ($user) {
            \Illuminate\Support\Facades\Cache::forget('cache_key_user_id_'.$user->id);
        }else{
            return $this->resFailed(500,'无效参数');
        }
    }

    public function makeChinaArea()
    {
        $arr = file_get_contents(storage_path('app/public/pcas-code.json'));
        $arr = json_decode($arr, true);
        $area = [];
        $code_str = '000000';
        foreach ($arr as $value) {
            $value['code'] = strlen($value['code']) === 6 ?: $value['code'] . substr($code_str, strlen($value['code']), 6);
            $area['province_list'][$value['code']] = $value['name'];
            foreach ($value['children'] as $vo) {
                $vo['code'] = strlen($vo['code']) === 6 ?: $vo['code'] . substr($code_str, strlen($vo['code']), 6);
                $area['city_list'][$vo['code']] = $vo['name'];
                foreach ($vo['children'] as $v) {
                    $area['county_list'][$v['code']] = $v['name'];
                }
            }
        }

        print_r($area);
        file_put_contents(storage_path('app/public/vantArea.json'), json_encode($area, JSON_UNESCAPED_UNICODE));
    }

    /**
     * [turn64 图片地址转64编码]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function turn64()
    {
        $url = request('url');
        if (!$url) {
            return $this->resFailed(414,'参数必填');
        }
        $image_info = getimagesize($url);
        $image_data = file_get_contents($url);
        // $image_data = fread(fopen($url,'r'),filesize(rtrim($url)));
        $base64 = 'data:'.$image_info['mime'].';base64,'.chunk_split(base64_encode($image_data));
        return $this->resSuccess($base64);
    }

    /**
     * 首页下拉获取热卖商品
     * @author: huiho <429294135@qq.com>
     * @param SiteConfigRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndexHot(SiteConfigRepository $repository)
    {
        return $this->resSuccess($repository->getIndexHot('index',$this->GMID));
    }


    /**
     * [itemList 获取集团项目列表]
     * @return [type] [description]
     */
    public function itemList(Request $request, GmPlatformRepository $repository)
    {
        $input = $request->all();
        $data = $repository->normalLists($input);
        // $types = ['normal'];
        // $data = GmPlatform::where('status',1)->whereIn('type',$types)->orderBy('listorder','desc')->select('gm_id','platform_name')->get();
        return $this->resSuccess($data);
    }

    /**
     * [itemListLogin 获取集团项目列表-登录状态]
     * @return [type] [description]
     */
    public function itemListLogin(Request $request, GmPlatformRepository $repository)
    {
        $input = $request->all();
        $data = $repository->normalLists($input);
        foreach ($data as $key => $value) {
            $user_default = -1;
            $user_id = $this->user->id;
            $default = UserRelYitianInfo::where(['user_id'=>$user_id,'default'=>1])->value('gm_id');
            $user_default = empty($default) ? 0 : $default;
            $data[$key]['user_default'] = $user_default;
        }
        return $this->resSuccess($data);
    }


    /**
     * [itemNearby 获取最近集团项目]
     * @return [type] [description]
     */
    public function itemNearby(Request $request, GmPlatformRepository $repository)
    {
        $input = $request->all();
        $data = $repository->normalLists($input);
        $form = [$input['lng'],$input['lat']];
        $gm_arr = [];
        $server = new ShopsService();
        $gm_id = 0;
        $min = 0;
        foreach ($data as $key => $value) {
            $to = [$value->longitude,$value->latitude];
            $temporarily = $server->get_distance($form,$to);
            if($key == 0){
                $min = $temporarily;
                $gm_id = $value->gm_id;
            }else{

                if($temporarily<$min){
                    $min = $temporarily;
                    $gm_id = $value->gm_id;
                }
            }
            $gm_arr[$value->gm_id]['distance'] = $temporarily;
            $gm_arr[$value->gm_id]['platform'] = $value->platform_name;
        }
       $result = ['gm_id'=>$gm_id,'platform'=> $gm_arr[$gm_id]['platform']];
        return $this->resSuccess($result);
    }


    /**
     * [showLive 直播]
     * @return [type] [description]
     */
    public function showLive(ConfigRepository $repository)
    {
        return $this->resSuccess($repository->configItem('wechat', 'live',$this->GMID));
    }

    /**
     * [getRecommend 下拉获取为你推荐]
     * @param  SiteConfigRepository $repository [description]
     * @return [type]                           [description]
     */
    public function getRecommend(Request $request,RecommendConfigRepository $repository)
    {
        $id = 0;
        if (!isset($request->title_id)) 
        {
            $title = $repository->getRecommendTitle($request->pages,$this->GMID);
            if (!empty($title)) 
            {
                $id = $title[0]['id'];
            }
        }
        $title_id = $request->title_id??$id;
        return $this->resSuccess($repository->getRecommend($request->pages,$title_id,$this->GMID));
    }
    /**
     * [getRecommend 为你推荐导航]
     * @param  SiteConfigRepository $repository [description]
     * @return [type]                           [description]
     */
    public function getRecommendTitle(Request $request,RecommendConfigRepository $repository)
    {
        return $this->resSuccess($repository->getRecommendTitle($request->pages,$this->GMID));
    }


    /**
     * 获取店铺列表
     * @Author hfh
     * @param Request $request
     * @param GmPlatformRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ShopList(Request $request, ShopRepository $repository)
    {
        $input = $request->all();
        $input['sign']='index-shop';
        $data = $repository->search($input);

        return $this->resSuccess($data);
    }

    /**
     * 获取店铺列表-登录状态
     * @Author hfh
     * @param Request $request
     * @param ShopRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ShopListLogin(Request $request)
    {
        $input = $request->all();
      /*  $input['sign']='index-shop';
        $data = $repository->search($input);
        foreach ($data as $key => $value) {
            $user_default = -1;
            $user_id = $this->user->id;
            $default = UserRelShopInfo::where(['user_id'=>$user_id,'default'=>1])->value('shop_id');
            $user_default = empty($default) ? 0 : $default;
            $data[$key]['user_default'] = $user_default;
        }*/
        $user_id = $this->user->id;
        if(isset($input['remember']) && $input['remember'] ==1){
            $shop_id=$input['shop_id']??0;
            if($shop_id<=0){
                return $this->resFailed('414','店铺id必传！');
            }
            $data = UserRelShopInfo::where(['user_id'=>$user_id,'shop_id'=>$shop_id])->first();
            if(!empty($data)){
                if($data['default'] ==0){
                    UserRelShopInfo::where(['user_id'=>$user_id])->update(['default'=>0]);
                    $isset['default'] = 1;
                    UserRelShopInfo::where(['user_id'=>$user_id,'shop_id'=>$shop_id])->update($isset);
                }
                $data['default']=1;
            }else{
                UserRelShopInfo::where(['user_id'=>$user_id])->update(['default'=>0]);
                $data['mobile']=$this->user['mobile'];
                $data['user_id']=$user_id;
                $data['gm_id'] = $this->GMID;
                $data['shop_id'] = $input['shop_id'];
                $data['default'] = 1;

                UserRelShopInfo::create($data);
            }
            Cache::forever('shop_list_login_'.$user_id,$data);
        }else{

            $data = Cache::get('shop_list_login_'.$user_id, function() use ($user_id){

                return UserRelShopInfo::where(['user_id'=>$user_id,'default'=>1])->first();
            });
            if(empty($data)){
                $data['default']=0;
            }
        }

        return $this->resSuccess($data);
    }


    /**
     * 店铺最近距离
     * @Author hfh
     * @param ShopNearbyRequest $request
     * @param ShopRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ShopNearby(Request $request, ShopRepository $repository)
    {
        $input = $request->all();
        $input['sign']='index-shop';
        $data = $repository->search($input);

        if(isset($input['lng']) && isset($input['lat'])){
            $form = [$input['lng'],$input['lat']];
            $gm_arr = [];
            $server = new ShopsService();
            $gm_id = 0;
            $min = 0;
            foreach ($data as $key => $value) {
                $to = [$value->longitude,$value->latitude];
                $temporarily = $server->get_distance($form,$to);
                if($key == 0){
                    $min = $temporarily;
                    $gm_id = $value->id;
                }else{
                    if($temporarily<$min){
                        $min = $temporarily;
                        $gm_id = $value->id;
                    }
                }
                $gm_arr[$value->id]['shop_id'] = $value['id'];
                $gm_arr[$value->id]['distance'] = $temporarily;
                $gm_arr[$value->id]['distance_text'] = $temporarily.'千米';
                $gm_arr[$value->id]['shop_name'] = $value->shop_name;
                $gm_arr[$value->id]['address'] = $value->address;
                $gm_arr[$value->id]['longitude'] = $value->longitude;
                $gm_arr[$value->id]['latitude'] = $value->latitude;
            }
            $result=[];
            if(!empty($gm_arr)){
                //根据字段 distance 对数组 $gm_arr 进行降序排列
                $gm_arr_asc = array_column($gm_arr,'distance');
                array_multisort($gm_arr_asc,SORT_ASC,$gm_arr);

                $result['shop_list'] = $gm_arr;
                $result['nearby_shop'] =$gm_arr[0];
            }
        }else{
            $result['shop_list']=$data;
        }

        return $this->resSuccess($result);
    }

}