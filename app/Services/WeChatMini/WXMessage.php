<?php


namespace ShopEM\Services\WeChatMini;


use Exception;

class WXMessage
{
    private $pickup_temple_id = 'ZlHZbFa-w0j2Sh0SP_hNcYHWBGSv8eX3jRwxWPwq4so';  //  提货提醒模板
    private $ship_temple_id = 'CDMyJft3zhlrVIA3wjKnsG2o3HomRodupu46rPuz7OY';    //  发货物流通知
    private $order_temple_id = '2p5BGOT9wBifcpG5eAMtMk8v-UXd-2nVWkuT65tDW_k';   //  下单成功通知
    private $point_change_temple_id = 'RZgJavgr2moTPjjL1a2x92G35t3Y0UuMd9ID4n6MBjI';    //  积分变动提醒
    private $log_filename;

    function __construct()
    {
        $this->log_filename = [
            $this->pickup_temple_id =>  'pickup_error',
            $this->ship_temple_id =>  'ship_error',
            $this->order_temple_id =>  'order_error',
            $this->point_change_temple_id =>  'point_change_error',
        ];
    }

    /**
     * @param array $data ['goods_name'=>商品名称, 'shop_name'=>商户名称, 'tid'=>订单号, 'amount'=>付款金额, 'address'=>提货地址]
     * @param string $openid 用户微信的openid
     * @param string $page  点击模板卡片后的跳转页面
     * @return bool
     */
    public function pickupMessage(array $data, string $openid, string $page = '')
    {
        $data = [
            'touser'        =>  $openid,
            'template_id'   =>  $this->pickup_temple_id,
            'data'          =>  [
                'thing5'                =>  ['value'=>$data['goods_name']],
                'thing1'                =>  ['value'=>$data['shop_name']],
                'character_string2'     =>  ['value'=>$data['tid']],
                'amount3'               =>  ['value'=>$data['amount']],
                'thing4'                =>  ['value'=>$data['address']],
            ],
        ];

        if ($page) $data['page'] = $page;

        try {
            return $this->send($data);
        } catch (Exception $e) {
            workLog($e->getMessage(),'WXMessage','pickup_error');
            return false;
        }
    }

    /**
     * @param array $data  ['goods_name'=>商品名称, 'tid'=>订单号, 'logistics_company'=>物流公司, 'logistics_id'=>物流号, 'time'=>购买时间]
     * @param string $openid 用户微信的openid
     * @param string $page 点击模板卡片后的跳转页面
     * @return bool
     */
    public function shipMessage(array $data, string $openid, string $page = '')
    {
        $data = [
            'touser'        =>  $openid,
            'template_id'   =>  $this->ship_temple_id,
            'data'          =>  [
                'thing2'                =>  ['value'=>$data['goods_name']],
                'character_string4'     =>  ['value'=>$data['tid']],
                'thing5'                =>  ['value'=>$data['logistics_company']],
                'character_string6'     =>  ['value'=>$data['logistics_id']],
                'time1'                 =>  ['value'=>$data['time']],
            ],
        ];

        if ($page) $data['page'] = $page;

        try {
            return $this->send($data);
        } catch (Exception $e) {
            workLog($e->getMessage(),'WXMessage','ship_error');
            return false;
        }
    }

    /**
     * @param array $data ['goods_name'=>商品名称, 'status'=>订单状态, 'tid'=>订单号, 'amount'=>付款金额, 'time'=>下单时间]
     * @param string $openid 用户微信的openid
     * @param string $page 点击模板卡片后的跳转页面
     * @return bool
     */
    public function orderMessage(array $data, string $openid, string $page = '')
    {
        $data = [
            'touser'        =>  $openid,
            'template_id'   =>  $this->order_temple_id,
            'data'          =>  [
                'character_string1'     =>  ['value'=>$data['tid']],
                'date2'                 =>  ['value'=>$data['time']],
                'thing3'                =>  ['value'=>$data['goods_name']],
                'amount4'               =>  ['value'=>$data['amount']],
                'phrase6'               =>  ['value'=>$data['status']],
            ],
        ];

        if ($page) $data['page'] = $page;

        try {
            return $this->send($data);
        } catch (Exception $e) {
            workLog($e->getMessage(),'WXMessage','order_error');
            return false;
        }
    }

    /**
     * @param array $data ['username'=>账户名称, 'change'=>变动数量, 'point'=>当前积分, 'time'=>变动时间, 'reason'=>变动原因]
     * @param string $openid 用户微信的openid
     * @param string $page 点击模板卡片后的跳转页面
     * @return bool
     */
    public function pointChangeMessage(array $data, string $openid, string $page = '')
    {
        $data = [
            'touser'        =>  $openid,
            'template_id'   =>  $this->point_change_temple_id,
            'data'          =>  [
                'thing1'                =>  ['value'=>$data['username']],
                'character_string2'     =>  ['value'=>$data['change']],
                'number3'               =>  ['value'=>$data['point']],
                'date4'                 =>  ['value'=>$data['time']],
                'thing5'                =>  ['value'=>$data['reason']],
            ],
        ];

        if ($page) $data['page'] = $page;

        try {
            return $this->send($data);
        } catch (Exception $e) {
            workLog($e->getMessage(),'WXMessage','point_change_error');
            return false;
        }
    }

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     */
    private function send(array $data)
    {
        $APPID = env('WECHAT_MINI_APPID_GROUP');
        $APPSECRET = env('WECHAT_MINI_APPSECRET_GROUP');

        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";

        session_start();
        $_SESSION['access_token'] = "";
        $_SESSION['expires_in'] = 0;


        if (!isset($_SESSION['access_token']) || (isset($_SESSION['expires_in']) && time() > $_SESSION['expires_in'])) {

            $json = $this->httpRequest($access_token);
            $json = json_decode($json, true);

            if(!isset($json['access_token'])){
                throw new Exception($json['errmsg']);
            }

            $_SESSION['access_token'] = $json['access_token'];
            $_SESSION['expires_in'] = time() + 7200;
            $ACCESS_TOKEN = $json["access_token"];

        } else {
            $ACCESS_TOKEN = $_SESSION["access_token"];
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$ACCESS_TOKEN;
        $result = json_decode($this->httpRequest($url,json_encode($data),'POST'),true);

        if ($result['errcode'] > 0) {
            workLog($result['errmsg'],'WXMessage',$this->log_filename[$data['template_id']]);
            return false;
        }
        return true;
    }

    /**
     * @param string $url
     * @param string $data
     * @param string $method
     * @return bool|string
     */
    private function httpRequest(string $url, string $data = '', string $method = 'GET')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}
