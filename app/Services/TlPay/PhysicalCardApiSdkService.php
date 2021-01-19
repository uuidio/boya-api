<?php


namespace ShopEM\Services\TlPay;

use phpseclib\Crypt\DES;
use Illuminate\Support\Facades\Validator;

class PhysicalCardApiSdkService
{
    private $base_url;
    private $app_key;
    private $secret;
    private $data_secret;
    private $version = '1.0';
    private $format = 'json';
    private $sign_v = '1';
    private $brh_id;
    private $mer_id;
    private $api_stop;
    private $payment_id;

    public function __construct()
    {
        $this->base_url = env('TL_URL','http://116.228.64.55:8080/aop/rest');
        $this->app_key = env('TL_APP_KEY','test');
        $this->secret = env('TL_SECRET','test');
        $this->data_secret = env('LT_DATA_SECRET','abcdefgh');
        $this->brh_id = env('TL_BRH_ID','0229000040');
        $this->mer_id = env('TL_MER_ID', '999990053990001');
        $this->payment_id = env('TL_PAY_ID', '0000000002');
        $this->api_stop = env('TL_API_STOP',false);
    }

    /**
     * 客户信息查询
     * @param string $mobile 手机号
     * @return bool|mixed|string
     */
    private function fetchUserInfo(string $mobile)
    {
        if (empty($mobile)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.customerinfoqj.query',
            'brh_id'    =>  $this->brh_id,
            'phone_num' =>  $mobile,
        ];
        return $this->request('get',$data);
    }

    /**
     * 查询卡信息(暂无用）
     * @param string $card 卡号
     * @return bool|mixed|string
     */
    private function fetchCardInfo(string $card)
    {
        if (empty($card)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.cardinfo.get',
            'card_id'   =>  $card,
            'order_id'  =>  getMicroTime(),
        ];
        return $this->request('get',$data);
    }

    /**
     * 获取绑定卡列表（暂无用）
     * @param $custom_id
     * @return bool|mixed|string
     */
    private function fetchBindCardList($custom_id)
    {
        if (empty($custom_id)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.cif.getcard',
            'cust_id'   =>  $custom_id,
            'brh_id'    =>  $this->brh_id,
            'order_id'  =>  getMicroTime(),
        ];
        return $this->request('get',$data);
    }

    /**
     * 客户卡信息查询
     * @param string $custom_id 客户号
     * @param string $serial 流水号
     * @param string $operator 操作员
     * @param int $flag 是否返回0余额卡片
     * @return bool|mixed|string
     */
    private function fetchMemberCardInfo(string $custom_id,string $serial,string $operator = '', $flag = 1)
    {
        if (empty($custom_id) || empty($serial)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.cifcardinfo.get',
            'brh_id'    =>  $this->brh_id,
            'cust_id'   =>  $custom_id,
            'order_id'  =>  $serial,
            'flag'      =>  $flag,
        ];
        if ($operator) $data['opr_id'] = $operator;
        return $this->request('get',$data);
    }

    /**
     * 单卡绑定/解绑
     * @param string $serial 流水号
     * @param string $card_id 卡号
     * @param string $custom_id 通联客户号
     * @param string $mode 操作
     * @return bool|mixed|string
     */
    private function cardAction(string $serial,string $card_id,string $custom_id,string $mode)
    {
        if (empty($serial) || empty($card_id) || empty($custom_id)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.cifcardqj.bind',
            'order_id'  =>  $serial,
            'card_id'   =>  $card_id,
            'cust_id'   =>  $custom_id,
            'brh_id'    =>  $this->brh_id,
            'type'      =>  2,
            'flg'       =>  ($mode == 'untie')?'3':'0',
        ];
        return $this->request('get',$data,true);
    }

    /**
     * 支付记录
     * @param string $custom_id 通联客户号
     * @param string $page 页码
     * @param string $begin 日期开始
     * @param string $end 日期结束
     * @return bool|mixed|string
     */
    private function payRecord(string $custom_id,string $page,string $begin, string $end)
    {
        if (empty($custom_id) || empty($page) || empty($begin) || empty($end)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.txnlog.search',
            'begin_date'=>  date('Ymd',strtotime('-90 day')),
            'end_date'  =>  date('Ymd'),
            'cust_id'   =>  $custom_id,
            'page_no'   =>  $page,
            'page_size' =>  '20',
        ];
        return $this->request('get',$data);
    }

    /**
     * 验证卡密
     * @param string $card_id 卡号
     * @param string $password 密码
     * @param string $serial 流水号
     * @return bool|mixed|string
     */
    private function verifyPassword(string $card_id, string $password, string $serial)
    {
        if (empty($card_id) || empty($password) || empty($serial)) return false;
        $data = [
            'method'    =>  'allinpay.card.cardpassword.validate',
            'order_id'  =>  $serial,
            'password'    =>  $this->desEncrypt($password),
            'card_id'   =>  $card_id,
        ];
        return $this->request('get',$data);
    }

    /**
     * 单卡激活（暂无用）
     * @param string $serial 流水号
     * @param string $card_id 需要激活的卡号
     * @param string $type 激活途径
     * @param string $desc 激活说明
     * @return bool|mixed|string
     */
    private function activateCardOne(string $serial,string $card_id,string $type,string $desc)
    {
        if (empty($serial) || empty($card_id) || empty($type)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.cardsingleactive.add',
            'order_id'  =>  $serial,
            'type'      =>  $type,
            'card_id'   =>  $card_id,
            'desc'      =>  $desc,
        ];
        return $this->request('get',$data,true);
    }

    /**
     * 多卡消费(无密)
     * @param array $data
     *
     * $data = [
     *   'order_id'     =>  流水号,
     *   'type'         =>  交易类型,
     *   'mer_tm'       =>  交易时间,
     *   'mer_order_id' =>  商户订单号,
     *   'payment_id'   =>  支付活动,(4位数字)
     *   'amounts'      =>  交易金额,(多个用‘|’隔开)
     *   'card_ids'     =>  卡号,(多卡用‘|’隔开)
     *   'total_qt'     =>  总张数,
     *   'total_at'     =>  总金额,
     *   'misc'         =>  商户自定义信息,
     *   'user_fee'     =>  用户手续费,（单位分）
     *   'user_name'    =>  用户名称,
     *   'goods_name'   =>  商品名称,
     *   'goods_type'   =>  商品种类,
     *   'goods_id'     =>  商品号,
     *   'goods_misc'   =>  商品备注
     * ]
     *
     * @return bool|mixed|string
     */
    private function pay(array $data)
    {
        $validator = Validator::make($data,[
            'order_id'      =>  'required',
            'type'          =>  'required',
            'mer_tm'        =>  'required',
            'mer_order_id'  =>  'required',
            'total_at'      =>  'required',
            'total_qt'      =>  'required',
            'card_ids'      =>  'required',
            'amounts'       =>  'required',
        ]);
        if ($validator->fails()) return false;
        $data['method'] = 'allinpay.card.multicardpay.add';
        $data['pay_cur'] = 'CNY';
        $data['payment_id'] = $this->payment_id;
        $data['mer_id'] = $this->mer_id;
        testLog(['request_pay'=>$data]);
        return $this->request('post',$data,true);
    }

    /**
     * 多卡退货
     * @param array $data
     *
     * $data = [
     *   'order_id'     =>  流水号,
     *   'trans_date'   =>  交易时间,
     *   'ori_order_id' =>  订单号,
     *   'amounts'      =>  交易金额,(多个用‘|’隔开)
     *   'card_ids'     =>  卡号,(多卡用‘|’隔开)
     *   'total_qt'     =>  卡总张数,
     *   'total_at'     =>  总金额,
     *   'misc'         =>  商户自定义信息,
     *   'back_order_id'=>  退货订单号,
     * ]
     *
     * @return bool|mixed|string
     */
    private function return(array $data)
    {
        $validator = Validator::make($data,[
            'order_id'      =>  'required',
            'ori_order_id'  =>  'required',
            'trans_date'    =>  'required',
            'total_at'      =>  'required',
        ]);
        if ($validator->fails()) return false;
        $data['method'] = 'allinpay.ppcs.multicardback.add';
        $data['mer_id'] = $this->mer_id;
        return $this->request('post',$data,true);
    }

    /**
     * 会员注册开卡
     * @param string $serial 流水号
     * @param string $mobile 手机号
     * @param string $opr_id 操作员
     * @param string $brand_id 4位品牌号
     * @return bool|mixed|string
     */
    private function memberRegister(string $serial, string $mobile, string $opr_id,string $brand_id)
    {
        if (empty($mobile)) return false;
        $data = [
            'method'    =>  'allinpay.ppcs.regcifcardopen.add',
            'order_id'  =>  $serial,
            'brh_id'    =>  $this->brh_id,
            'opr_id'    =>  $opr_id,
            'brand_id'  =>  $brand_id,
            'mobile'    =>  $mobile,
        ];
        return $this->request('post',$data,true);
    }

    /**
     * 会员注册开卡查询（暂无用）
     * @param string $serial 流水号
     * @param string $order_id 商户订单号
     * @param string $date 交易日期
     * @return bool
     */
    private function memberRegisterCheck(string $serial,string $order_id,string $date)
    {
        if (empty($serial) || empty($order_id) || empty($date)) return false;
        $data = [
            'method'        =>  'allinpay.ppcs.card.cardopenwithcif.get',
            'order_id'      =>  $serial,
            'brh_id'        =>  $this->brh_id,
            'mer_order_id'  =>  $order_id,
            'trans_date'    =>  $date,
        ];
        return $this->request('post',$data);
    }

    /**
     * @param string $api
     * @param mixed ...$data
     * @return array
     */
    public function pushApi(string $api,...$data)
    {
        if ($this->api_stop) return ['code' => 2, 'msg' => '推送已关闭', 'res' => []];
        $res = $this->$api(...$data);
        if (!$res) return ['code' => 3, 'msg' => '传参错误', 'res' => []];
        if (is_string($res)) return ['code' => 1, 'msg' => '请求失败', 'res' => []];
        return ['code' => 0, 'msg' => '请求成功', 'res' => $res];
    }

    private function request(string $type, $data,$makeLog = false)
    {
        $data['app_key'] = $this->app_key;
        $data['timestamp'] = date('YmdHis');
        $data['v'] = $this->version;
        $data['format'] = $this->format;
        $data['sign_v'] = $this->sign_v;
        $data['sign'] = $this->getSign($data);
        try {
            if ($type == 'get') {
                $res = json_decode((new \GuzzleHttp\Client())->request('GET',$this->base_url,['query' => $data])->getBody()->getContents(),true);
            } else {
                $res = json_decode((new \GuzzleHttp\Client())->request('POST',$this->base_url,['form_params' => $data])->getBody()->getContents(),true);
            }
            if ($makeLog) {
                workLogBuilder([
                    'status'=>  1,
                    'params'=>  $data,
                    'res'   =>  $res,
                ],'tl','push');
            }
        } catch (\Exception $exception) {
            workLogBuilder([
                'status'=>  2,
                'params'=>  $data,
                'res'   =>  $exception->getMessage(),
            ],'tl','push');
            return $exception->getMessage();
        }
        return $res;
    }

    /**
     * 获取签名
     * @param array $data
     * @return string
     */
    private function getSign(array $data)
    {
        ksort($data);
        $str = $this->secret;
        foreach ($data as $key => $value) {
            $str .= $key.$value;
        }
        $str .= $this->secret;

        return strtoupper(md5($str));
    }

    /**
     * des加密
     * @param $data
     * @return string
     */
    private function desEncrypt($data)
    {
        $des = new DES();
        $des->setKey($this->data_secret);
        $des->setIV($this->data_secret);
        return base64_encode($des->encrypt(date('YmdHis').'aop'.$data));
    }
}
