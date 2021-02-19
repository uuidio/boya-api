<?php
/**
 * @Filename LogisticsService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use ShopEM\Models\LogisticsDelivery;
use ShopEM\Models\LogisticsDeliveryDetail;
use ShopEM\Models\LogisticsDlycorp;
use GuzzleHttp\Client;

class LogisticsService
{


    /**
     * 创建发货单
     *
     * @Author hfh_wind
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function deliveryCreate($params)
    {

        $orders = $params['tradeInfo']['trade_order'];
        $trades = $params['tradeInfo'];

        $deliveryId = $this->_getDeliveryId($params['tid']);
        $delivery = array(
            'delivery_id'       => $deliveryId,
            'tid'               => $trades['tid'],
            'user_id'           => $trades['user_id'],
            'shop_id'           => $params['shop_id'],
            'seller_id'         => $params['seller_id'],
            'post_fee'          => $trades['post_fee'],
            'is_protect'        => 0,
            'receiver_name'     => $trades['receiver_name'],
            'receiver_province' => $trades['receiver_province'],
            'receiver_city'     => $trades['receiver_city'],
            'receiver_district' => $trades['receiver_county'],
            'receiver_address'  => $trades['receiver_address'],
            'receiver_zip'      => $trades['receiver_zip'],
            'receiver_mobile'   => $trades['receiver_tel'],
            'receiver_phone'    => $trades['receiver_tel'],
            'status'            => 'ready',
        );

        DB::beginTransaction();
        try {
            $result = LogisticsDelivery::create($delivery);

            if (!$result) {
                throw new \LogicException('发货单创建失败');
            }

            foreach ($orders as $key => $order) {
                $detail = [];
                $detail['delivery_id'] = $deliveryId;
                $detail['oid'] = $order['oid'];
                $detail['item_type'] = "item";
                $detail['sku_id'] = $order['sku_id'];
                $detail['sku_bn'] = $order['goods_serial'];
                $detail['sku_title'] = $order['goods_name'];
                $detail['number'] = $order['quantity'];
                $isSave = LogisticsDeliveryDetail::create($detail);
                if (!$isSave) {
                    throw new \LogicException("发货单明细保存失败");
                }

                //当有赠品时，发货单记录赠品数据
                if (!empty($order['gift_data'])) {
                    $this->__saveGiftDetail($order, $deliveryId);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        return $deliveryId;
    }

    private function __saveGiftDetail($order, $deliveryId)
    {
        $giftData = $order['gift_data'];
        foreach ($giftData as $gift) {
            $detail = [];
            $detail['delivery_id'] = $deliveryId;
            $detail['oid'] = $order['oid'];
            $detail['item_type'] = "gift";
            $detail['sku_id'] = $gift['sku_id'];
            $detail['sku_bn'] = $gift['bn'];
            $detail['sku_title'] = $gift['title'];
            $detail['number'] = $gift['gift_num'];
            $isSave = LogisticsDeliveryDetail::create($detail);
            if (!$isSave) {
                throw new \LogicException("发货单明细保存失败");
            }
        }
        return true;
    }


    /**
     * 发货单更新
     *
     * @Author hfh_wind
     * @param $params
     * @return mixed
     * @throws LogicException
     */

    public function deliveryUpdate($params)
    {
        $data = LogisticsDelivery::where(['delivery_id' => $params['delivery_id']])->first()->toArray();
        if ($data) {
            $data['detail'] = LogisticsDeliveryDetail::where(['delivery_id' => $params['delivery_id']])->get()->toArray();
        }

        if (!$data) {
            throw new LogicException('发货失败，发货单有误');
        }

        $corp = LogisticsDlycorp::select('corp_name', 'corp_code', 'id')->where('corp_code',
            $params['corp_code'])->first();
        if (!$corp) {
            throw new LogicException('发货失败，物流公司有误');
        }

        if (strtoupper($params['corp_code']) != strtoupper($corp['corp_code'])) {
            throw new LogicException('快递公司不匹配！发货失败');
        }

        $delivery['corp_code'] = $corp['corp_code'];
        $delivery['logi_id'] = $corp['id'];//corp_id
        $delivery['logi_name'] = $corp['corp_name'];
        $delivery['logi_no'] = trim($params['logi_no']);
        $delivery['delivery_id'] = $params['delivery_id'];
//        $delivery['dlytmpl_id'] = $params['template_id'];
        $delivery['dlytmpl_id'] = 0;
        $delivery['post_fee'] = $params['post_fee'];
        $delivery['is_protect'] = 0;
        $delivery['memo'] = $params['memo'];
        $delivery['status'] = "succ";
        $delivery['t_send'] = Carbon::now()->toDateTimeString();
        $delivery['t_confirm'] = Carbon::now()->toDateTimeString();
        $isSave = LogisticsDelivery::where([
            'delivery_id' => $params['delivery_id'],
            'tid'         => $params['tid']
        ])->update($delivery);
        if (!$isSave) {
            throw new LogicException('更新订单发货单失败');
        }
        return $data;
    }


    private function _getDeliveryId($tid)
    {
        $sign = '1' . date("Ymd");
        $microtime = microtime(true);
        mt_srand($microtime);
        $randval = substr(mt_rand(), 0, -3) . rand(100, 999);
        return $sign . $randval;
    }

    /**
     * 获取Ems物流信息
     * @Author hfh_wind
     * @return mixed
     */
    public function getEMS($code)
    {

        try {
            $url = 'http://211.156.193.140:8000/cotrackapi/api/track/mail/' . $code;
            $httpClient = new Client();

            $response = $httpClient->request('GET', $url, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'authenticate' => '9796C672E5BD391AE053D2C2020A1569',
                    'version'      => 'ems_track_cn_3.0',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Post common' . $e->getMessage());
            return $e->getMessage();
        }

        if ($response->getStatusCode() === 200) {
            return json_decode($response->getBody()->getContents(), true);
        }
    }




    /**
     * 从快递100提供的的物流跟踪API，获取物流轨迹
     * @Author hfh_wind
     * @param string $LogisticCode 物流单号
     * @param string $ShipperCode 快递公司编号
     * @return mixed|string
     */
    public function Pullkd100($LogisticCode, $ShipperCode)
    {

        $logisticsCompany = $ShipperCode;
        $num = $LogisticCode;

        $param = array(
            'com' => $logisticsCompany,
            'num' => $num,
        );

        $param = json_encode($param);

//        $customer = 'FCAD357DBAB675562B7793573CAEB21A';
        $customer = '4E164922A258757AE0D258C59DF8F39D';

//        $key = "TFLLxqMh4295";
        $key = "qPAdLYcg7142";

        $kd100ApiUrl = 'http://poll.kuaidi100.com/poll/query.do';
        //签名
        $sign = md5($param . $key . $customer);

        $sign = strtoupper($sign);

        $post_data_lstParams = array(
            'param'    => $param,
            'customer' => $customer,
            'sign'     => $sign,
        );

//        查询失败，请到快递公司官网查询
        try {
            $httpClient = new Client();
            $respond = $httpClient->request('POST', $kd100ApiUrl, ['form_params' => $post_data_lstParams]);
            if ($respond->getStatusCode() === 200) {
                $result = $respond->getBody()->getContents();

                if ($result) {
                    $result = json_decode($result, true);

                    return $result;
                }
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


}