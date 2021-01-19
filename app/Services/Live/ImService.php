<?php
/**
 * @Filename        ImService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 *
 *
 */
namespace ShopEM\Services\Live;



class ImService
{
    private $AppKey;                //开发者平台分配的AppKey
    private $AppSecret;             //开发者平台分配的AppSecret,可刷新
    private $Nonce;					//随机数（最大长度128个字符）
    private $CurTime;             	//当前UTC时间戳，从1970年1月1日0点0 分0 秒开始到现在的秒数(String)
    private $CheckSum;				//SHA1(AppSecret + Nonce + CurTime),三个参数拼接的字符串，进行SHA1哈希计算，转化成16进制字符(String，小写)
    const   HEX_DIGITS = "0123456789abcdef";

    /**
     * 参数初始化
     * @param $AppKey
     * @param $AppSecret
     * @param $RequestType [选择php请求方式，fsockopen或curl,若为curl方式，请检查php配置是否开启]
     */
    public function __construct(){
        $this->AppKey    = 'df48db86188bffb69b6940d661200faf';
        $this->AppSecret = '202f19c3d80d';
        $this->RequestType = 'curl';
    }

    /**
     * 主播开启聊天室服务
     *
     * @param  $roomid    [聊天室ID]
     * @param  $accid     [云信ID]

     * @return $result    [返回array数组对象]
     */
    public function roomBegin($param)
    {
        $this->chatroomToggle(['roomid'=>$param['roomid'],'operator'=>$param['accid'],'valid'=>'true']);

        $chatroom = $this->get(['roomid'=>$param['roomid'],'count'=>'false']);
        if(!$chatroom['chatroom']['valid']) {
            throw new \LogicException('聊天室开启失败，请联系管理员');
        }

        $addr = $this->getAddr(['roomid'=>$param['roomid'],'accid'=>$param['accid'],'clienttype'=>'2','clientip'=>'']);
        if($addr['code'] != '200') {
            throw new \LogicException('聊天室开启失败，请联系管理员');
        }

        return $addr['addr'];
    }


    /**
     * 创建云信ID
     * 1.第三方帐号导入到云信平台；
     * 2.注意accid，name长度以及考虑管理秘钥token
     * @param  $accid     [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $name      [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $props     [json属性，第三方可选填，最大长度1024字节]
     * @param  $icon      [云信ID头像URL，第三方可选填，最大长度1024]
     * @param  $token     [云信ID可以指定登录token值，最大长度128字节，并更新，如果未指定，会自动生成token，并在创建成功后返回]
     * @return $result    [返回array数组对象]
     */
    public function createUser($accid)
    {
        $url = 'https://api.netease.im/nimserver/user/create.action';
        $data= array(
            'accid'  =>  $accid,
        );
        $result = $this->postDataCurl($url,$data);

        return $result;
    }

    public function updateUser($data)
    {
        $url = 'https://api.netease.im/nimserver/user/updateUinfo.action';
        $data= array(
            'accid'  =>  $data['accid'],
            'name'  =>  $data['name']
        );
        $result = $this->postDataCurl($url,$data);

        return $result;
    }

    /**
     * 获取用户名片
     * @param $accids
     * @return array
     */
    public function getUsers($data)
    {
        $url = 'https://api.netease.im/nimserver/user/getUinfos.action';
        $data = [
            'accids' => json_encode($data['accids']),
        ];
        $result = $this->postDataCurl($url,$data);

        return $result;
    }

    /**
     * 更新云信token
     * @param $accid
     * @return array
     */
    public function updateToken($accid)
    {
        $url = 'https://api.netease.im/nimserver/user/update.action';
        $data= array(
            'accid'  =>  $accid,
        );
        $result = $this->postDataCurl($url,$data);

        return $result;
    }

    /**
     * 重置云信token
     * @param $accid
     * @return array
     */
    public function refreshToken($accid)
    {
        $url = 'https://api.netease.im/nimserver/user/update.action';
        $data= array(
            'accid'  =>  $accid,
        );
        $result = $this->postDataCurl($url,$data);

        return $result;
    }


    /**
     * 创建直播间
     * @param creator 云信ID
     *        name 聊天室名称
     * @return $result    [返回array数组对象]
     */
    public function createChatroom($data)
    {
        $url = 'https://api.netease.im/nimserver/chatroom/create.action';
        $data= array(
            'creator'  =>  $data['creator'],
            'name'     =>  $data['name']
        );

        $result = $this->postDataCurl($url,$data);
        return $result;
    }

    /**
     * 获取聊天室信息
     * @param roomid 聊天室id
     *        needOnlineUserCount 是否需要返回在线人数，true或false，默认false
     * @return $result    [返回array数组对象]
     */
    public function get($param)
    {
        $url = 'https://api.netease.im/nimserver/chatroom/get.action';
        $data= array(
            'roomid'  =>  $param['roomid'],
            'needOnlineUserCount' => $param['count'],
        );

        $result = $this->postDataCurl($url,$data);
        return $result;
    }

    /**
     * 设置聊天室内用户角色
     * @param roomid 聊天室id
     *        needOnlineUserCount 是否需要返回在线人数，true或false，默认false
     * @return $result    [返回array数组对象]
     */
    public function setMemberRole($param)
    {
        $url = 'https://api.netease.im/nimserver/chatroom/setMemberRole.action';
        $data= array(
            'roomid'  =>  $param['roomid'],
            'operator' => $param['operator'],
            'target' => $param['target'],
            'opt' => $param['opt'],
            'optvalue' => $param['optvalue'],
        );

        $result = $this->postDataCurl($url,$data);
        return $result;
    }

    /**
     * 修改聊天室开/关闭状态
     * @param roomid 聊天室ID
     *        operator 操作者账号，必须是创建者才可以操作
     *        valid   true或false，false:关闭聊天室；true:打开聊天室
     * @return $result    [返回array数组对象]
     */
    public function chatroomToggle($param)
    {
        $url = 'https://api.netease.im/nimserver/chatroom/toggleCloseStat.action';
        $data= array(
            'roomid'  =>  $param['roomid'],
            'operator'=>  $param['operator'],
            'valid'   =>  $param['valid']
        );

        $result = $this->postDataCurl($url,$data);
        return $result;
    }

    /**
     * 请求聊天室地址
     * @param roomid 聊天室id
     *        accid 进入聊天室的账号
     *        clienttype 1:weblink（客户端为web端时使用）; 2:commonlink（客户端为非web端时使用）;3:wechatlink(微信小程序使用), 默认1
     *        clientip 客户端ip，传此参数时，会根据用户ip所在地区，返回合适的地址
     * @return $result    [返回array数组对象]
     */
    public function getAddr($param)
    {
        $url = 'https://api.netease.im/nimserver/chatroom/requestAddr.action';
        $data= array(
            'roomid'    => $param['roomid'],
            'accid'     => $param['accid'],
            'clienttype'=> $param['clienttype']
        );

        $result = $this->postDataCurl($url,$data);
        return $result;
    }


    public function getMembers($param)
    {
        $url = 'https://api.netease.im/nimserver/chatroom/membersByPage.action';
        $data= array(
            'roomid'    => $param['roomid'],
            'type'     => $param['type'],
            'endtime'=> '0',
            'limit'=> '100'
        );
        $result = $this->postDataCurl($url,$data);
        return $result;
    }

    /**
     * API checksum校验生成
     * @param  void
     * @return $CheckSum(对象私有属性)
     */
    public function checkSumBuilder(){
        //此部分生成随机字符串
        $hex_digits = self::HEX_DIGITS;
        $this->Nonce;
        for($i=0;$i<128;$i++){			//随机字符串最大128个字符，也可以小于该数
            $this->Nonce.= $hex_digits[rand(0,15)];
        }
        $this->CurTime = (string)(time());	//当前时间戳，以秒为单位

        $join_string = $this->AppSecret.$this->Nonce.$this->CurTime;
        $this->CheckSum = sha1($join_string);
    }



    /**
     * 使用CURL方式发送post请求
     * @param  $url     [请求地址]
     * @param  $data    [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postDataCurl($url,$data){
        $this->checkSumBuilder();       //发送请求前需先生成checkSum

        $timeout = 5000;
        $http_header = array(
            'AppKey:'.$this->AppKey,
            'Nonce:'.$this->Nonce,
            'CurTime:'.$this->CurTime,
            'CheckSum:'.$this->CheckSum,
            'Content-Type:application/x-www-form-urlencoded;charset=utf-8'
        );


        // $postdata = '';
        $postdataArray = array();
        foreach ($data as $key=>$value){
            array_push($postdataArray, $key.'='.urlencode($value));
            // $postdata.= ($key.'='.urlencode($value).'&');
        }
        $postdata = join('&', $postdataArray);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt ($ch, CURLOPT_HEADER, false );
        curl_setopt ($ch, CURLOPT_HTTPHEADER,$http_header);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (false === $result) {
            $result =  curl_errno($ch);
        }
        curl_close($ch);
        return $this->json_to_array($result) ;
    }

    /**
     * 将json字符串转化成php数组
     * @param  $json_str
     * @return $json_arr
     */
    public function json_to_array($json_str){
        if(is_array($json_str) || is_object($json_str)){
            $json_str = $json_str;
        }else if(is_null(json_decode($json_str))){
            $json_str = $json_str;
        }else{
            $json_str =  strval($json_str);
            $json_str = json_decode($json_str,true);
        }
        $json_arr=array();
        foreach($json_str as $k=>$w){
            if(is_object($w)){
                $json_arr[$k]= $this->json_to_array($w); //判断类型是不是object
            }else if(is_array($w)){
                $json_arr[$k]= $this->json_to_array($w);
            }else{
                $json_arr[$k]= $w;
            }
        }
        return $json_arr;
    }
}
