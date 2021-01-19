<?php
/**
 * @Filename LogisticsTemplatesServices.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use ShopEM\Models\Area;
use ShopEM\Models\Config;
use ShopEM\Models\LogisticsTemplate;
use ShopEM\Models\Payment;
use ShopEM\Models\ShopRegion;


class LogisticsTemplatesServices
{


    /**
     * 检查数据
     *
     * @Author hfh_wind
     * @param $data
     * @param $shopId
     * @return bool
     */
    public function __check($data, $shopId)
    {

        // 运费配置参数

        if ($data['valuation'] == '1' && $data['fee_conf']) {
//            $data['fee_conf'] = json_decode($data['fee_conf']);
            // 免邮配置参数
            if (isset($data['free_conf'])) {
//                $data['free_conf'] = json_decode($data['free_conf']);
            } else {
                $data['free_conf'] = '';
            }
        }
        if ($data['valuation'] == '2' && $data['fee_conf']) {
            if (!$data['fee_conf'][0]['start_standard'] || !$data['fee_conf'][0]['add_fee'] || !$data['fee_conf'][0]['add_standard'] || !$data['fee_conf'][0]['start_fee']) {
                $msg = '默认运费必填';
                throw new \LogicException($msg);
            }

//            $data['fee_conf'] = json_decode($data['fee_conf']);
            // 免邮配置参数
            if (isset($data['free_conf'])) {
//                $data['free_conf'] = json_decode($data['free_conf']);
            } else {
                $data['free_conf'] = '';
            }
        }
        if ($data['valuation'] == '3' && $data['fee_conf']) {

            if (count($data['fee_conf']) < 1 || $data['fee_conf'][0]['rules'][0]['basefee'] < 0) {
                $msg = '至少有一条规则必填';
                throw new \LogicException($msg);
            }
            // 判断是否有填运费，没有则不能提交模板
            foreach ($data['fee_conf'] as $key => $value) {

                if(!isset($value['addr'])&& $key != 0){
                    $msg = '添加地区必须勾选一个或以上！';
                    throw new \LogicException($msg);
                }
                if(!isset($value['rules'][0]['basefee']) || $value['rules'][0]['basefee'] == null){
                     $msg = '请填写运费再保存！';
                     throw new \LogicException($msg);
                }
            }
//            $data['fee_conf'] = json_decode($data['fee_conf']);
            $data['free_conf'] = '';
        }


        if (empty($data['name']) || mb_strlen(trim($data['name']), 'utf8') > 20) {
            $msg = '运费模板名称不能为空，且不可以超过20个字';
            throw new \LogicException($msg);
        }

        //修改的该模板ID是否存在
        $id = LogisticsTemplate::where(['name' => $data['name'], 'shop_id' => $shopId])->select('id')->first();
        if (isset($id['id']) && (!$data['id'] || $data['id'] != $id['id'])) {
            $msg = '该运费模板名称已存在';
            throw new \LogicException($msg);
        }

        if (!in_array($data['valuation'], array(1, 2, 3, 4))) {
            $msg = '请选择正确的计价方式';
            throw new \LogicException($msg);
        }

        $areaArr = array();
        if (!empty($data['fee_conf'])) {
            foreach ($data['fee_conf'] as $key => $row) {

                if (empty($row->area)) {
                    continue;
                }

                $area = explode(',', $row->area);

                foreach ($area as $areaId) {

                    $areaName = ShopRegion::where(['id' => $areaId])->first();

                    if (empty($areaName)) {
                        $msg = "参数错误，选择的地区不存在";
                        throw new \LogicException($msg);
                    }

                    if (in_array($areaId, $areaArr)) {
                        $msg = "地区({$areaName})配置重复";
                        throw new \LogicException($msg);
                    } else {
                        $areaArr[] = $areaId;
                    }
                }
            }
        }

        return true;
    }


    /**
     * 处理数据,返回需要的字段
     *
     * @Author hfh_wind
     * @param $data
     * @param $shopId
     * @return mixed
     */
    public function __preData($data, $shopId)
    {
        if (!empty($data['id'])) {
            $return['id'] = $data['id'];
        }
        $return['shop_id'] = $shopId;
        $return['name'] = trim($data['name']);
        $return['is_free'] = isset($data['is_free']) && !empty($data['is_free']) ? 1 : 0;
        $return['valuation'] = $data['valuation'];

        if (!empty($data['protect'])) {
            $return['protect'] = $data['protect'];
            $return['protect_rate'] = $data['protect_rate'];
            $return['minprice'] = $data['minprice'];
        } else {
            $return['protect'] = 0;
            $return['protect_rate'] = 0;
            $return['minprice'] = 0;
        }

        $return['status'] = $data['status'] == '0' ? '0' : '1';
        $return['fee_conf'] = $return['is_free'] ? '' : json_encode($data['fee_conf']);
        //是否开启免邮规则open_freerule =1
        if (isset($data['open_freerule']) && $data['open_freerule'] == 1) {
            $return['free_conf'] = !isset($data['is_free']) || empty($data['free_conf']) ? '' : json_encode($data['free_conf']);
        }


        return $return;
    }


    /**
     * 根据运费模板ID 和传入的重量，地区参数计算运费
     *
     * @Author hfh_wind
     * @param $templateId
     * @param $areaIds
     * @param $total_price
     * @param $total_quantity
     * @param $total_weight
     * @return bool|int
     */
    public function countFee($id, $areaIds, $total_price, $total_quantity, $total_weight)
    {
        $areaArr = Area::checkArea($areaIds);
        if (!$areaArr) {
            return false;
        }
        $areaIds = $areaArr['node'] . ',' . $areaArr['id'];

        $filter = array(
            'id' => $id,
//            'status' => '1',
        );
        $template = LogisticsTemplate::where($filter)->first();

        if (empty($template)) {
            $msg = "找不到运费模板，请联系商家！";
            throw new \LogicException($msg);
        } elseif ($template['status'] == 0) {
            //如果禁用了,邮费就为0
            return 0;
        }


        // 卖家免运费则直接返回运费为0；
        if ($template['is_free']) {
            $fee = 0;
        } else {
            $paramsCartData['total_weight'] = $total_weight;
            $paramsCartData['total_price'] = $total_price;
            $paramsCartData['total_quantity'] = $total_quantity;

            // 计算运费
            $fee = $this->__count($template, $areaIds, $paramsCartData);

            // 判断是否符合包邮规则
            $isFree = $this->__isFree($template, $areaIds, $paramsCartData);

            if ($isFree) {
                $fee = 0;
            }
        }

        return $fee;
    }

    /**
     * 根据传参计算出运费
     *
     * @param array $template 运费模板信息
     * @param string $areaIds 收货地区
     * @param int $paramsCartData 原始计算参数。对应运费模板的各商品总重量，总价钱，总购买数量
     *
     * @return int
     */
    private function __count($template, $areaIds, $paramsCartData)
    {
        $fee_conf = json_decode($template['fee_conf'], true);


        $areaIdsArr = explode(',', $areaIds);
        $feeConf = '';

        foreach ($fee_conf as $data) {
            if (!isset($data['area']) && empty($data['area'])) {
                $defaultConf = $data;
            } else {
                // 只要传入的地区中和配置的指定地区有一个匹配了，则表示地区运费按照本次循环的指定地区进行计算(因为，运费模板指定地区是不可以重复的，只要省、市、区与指定地区配置中的省、市、区有一个匹配了则匹配成功)
//                $areaSetting = explode(',', $data['area']);
                $areaSetting = $data['area'];
                $intersect = array_intersect($areaSetting, $areaIdsArr);//求交集，只要有一个符合则表示匹配成功，跳出循环
                if ($intersect) {
                    $feeConf = $data;
                    break;
                }
            }
        }
        $config = $feeConf ? $feeConf : $defaultConf;

        $fee = 0;
        if ($template['valuation'] == '1') {
            if ($paramsCartData['total_weight'] <= $config['start_standard']) {
                $fee = $config['start_fee'];
            } elseif ($config['add_standard'] > 0) {
                $addWeight = bcsub($paramsCartData['total_weight'], $config['start_standard'], 2);
                //$nums = bcdiv($addWeight, $config['add_standard'], 2);
                $nums = ceil(bcdiv($addWeight, $config['add_standard'], 2));
                $fee = bcadd($config['start_fee'], bcmul($nums, $config['add_fee'], 2), 2);
            }

        }
        if ($template['valuation'] == '2') {
            if ($paramsCartData['total_quantity'] <= $config['start_standard']) {
                $fee = $config['start_fee'];
            } elseif ($config['add_standard'] > 0) {
                $addNum = bcsub($paramsCartData['total_quantity'], $config['start_standard']);
                $beishu = ceil(bcdiv($addNum, $config['add_standard'], 2));
                $fee = bcadd($config['start_fee'], bcmul($config['add_fee'], $beishu, 2), 2);
            }
        }
        if ($template['valuation'] == '3') {

            foreach ($config['rules'] as $v) {

                if ($paramsCartData['total_price'] >= $v['up'] && $paramsCartData['total_price'] < $v['down']) {
                    $fee = $v['basefee'];
                }
            }

            if (!$fee) {
                $maxrule = end($config['rules']);
                if ($paramsCartData['total_price'] >= $maxrule['up']) {
                    $fee = $maxrule['basefee'];
                }
            }
        }

        return $fee;
    }


    /**
     * 根据配置参数判断是否符合免运费规则
     *
     * @Author hfh_wind
     * @param int $valuation 计价方式
     * @param array $freeConfig 运费模板运费配置
     * @param int $total_price 总价
     * @param int $total_quantity 总件数
     * @param int $total_weight 重量kg
     *
     * @return int
     */
    private function __isFree($template, $areaIds, $paramsCartData)
    {
        // 判断是否符合包邮规则
        $valuation = $template['valuation'];
        $areaIdsArr = explode(',', $areaIds);
        $total_weight = $paramsCartData['total_weight'];
        $total_price = $paramsCartData['total_price'];
        $total_quantity = $paramsCartData['total_quantity'];
        $free_conf = json_decode($template['free_conf'], true);

        $freeConf = '';
        if (empty($free_conf)) {
            return false;
        }
        foreach ($free_conf as $v) {
            if (empty($v['area'])) {
                $defaultFreeConf = $v;
            } else {
                // 只要传入的地区中和配置的指定地区有一个匹配了，则表示地区按照本次循环的指定地区进行判断(因为，包邮规则指定地区是不可以重复的，只要省、市、区与指定地区配置中的省、市、区有一个匹配了则匹配成功)
                $freeAreaSetting = is_array($v['area'])?$v['area']:explode(',', $v['area']);
                $intersect = array_intersect($freeAreaSetting, $areaIdsArr);//求交集，只要有一个符合则表示匹配成功，跳出循环

                if ($intersect) {
                    $freeConf = $v;
                    break;
                }
            }
        }

        $freeConfig = $freeConf ? $freeConf : $defaultFreeConf;

        if ($valuation == '1') {
            // 重量
            if ($freeConfig['freetype'] == '1') {
                return ($freeConfig['inweight'] && $total_weight <= $freeConfig['inweight']) ? true : false;
            }
            // 金额
            if ($freeConfig['freetype'] == '2') {
                return ($freeConfig['upmoney'] && $total_price >= $freeConfig['upmoney']) ? true : false;
            }
            // 重量+金额
            if ($freeConfig['freetype'] == '3') {
                return ($freeConfig['inweight'] && $freeConfig['upmoney'] && ($total_weight <= $freeConfig['inweight']) && ($total_price >= $freeConfig['upmoney'])) ? true : false;
            }
        } elseif ($valuation == '2') {
            // 件数
            if ($freeConfig['freetype'] == '1') {
                return ($freeConfig['upquantity'] && ($total_quantity >= $freeConfig['upquantity'])) ? true : false;
            }
            // 金额
            if ($freeConfig['freetype'] == '2') {
                return ($freeConfig['upmoney'] && ($total_price >= $freeConfig['upmoney'])) ? true : false;
            }
            // 件数+金额
            if ($freeConfig['freetype'] == '3') {
                return ($freeConfig['upquantity'] && $freeConfig['upmoney'] && ($total_quantity >= $freeConfig['upquantity']) && ($total_price >= $freeConfig['upmoney'])) ? true : false;
            }
        } else {
            return false;
        }
    }

    /**
     * 商品免邮
     * @Author hfh_wind
     * @param $amount
     */
    public function freeOrderPost($amount, $user_id,$gm_id=0)
    {

        $config_info = Config::where(['group' => 'free_order_amount','gm_id' => $gm_id])->first();

        if (empty($config_info) ||  empty($config_info['value'])) {
            //尚未配置
            $retrun['type']="none_config";
            $retrun['status'] = 0;
            return $retrun;
        }
        //规则
        $rules = json_decode($config_info['value'], true);
        $retrun['status'] = -1;
        //新用户免邮
        if (isset($rules['new_user_rules']) && $rules['new_user_rules']['status'] == 1) {
            //没实付过的用户是为新会员
            $checkPay = Payment::where(['user_id' => $user_id, 'status' => 'succ'])->count();
            if (!$checkPay) {
                $retrun['rule_order_amount']=0;
                $retrun['type']="new_free";
                return $retrun;
            }
        }

        // 判断是否符合包邮规则
        if (isset($rules['free_rules']) &&  $rules['free_rules']['status'] == 1) {
            $free_rules_fee = $rules['free_rules']['free_order_amount'];
            $retrun['rule_order_amount']=$free_rules_fee;
            $retrun['type']="trade_free";
            if ($amount >= $free_rules_fee) {
                return $retrun;
            }
        }

        // 减免运费
        $decr_rules = $rules['decr_rules']??0;
        if(empty($decr_rules)){
            $retrun['type']="none_config";
            $retrun['status'] = 1;
            $retrun['decr_fee'] = 0;
            return $retrun;
        }
        $decr_fee = $this->DecrRulesFee($amount, $decr_rules);

        $retrun['status'] = 1;
        $retrun['type']="decr_post";
        $retrun['decr_fee'] = $decr_fee['discount_post_fee']; //减免的邮费
        $retrun['rule_order_amount'] = $decr_fee['rule_order_amount'];  //减免规则金额
        return $retrun;
    }


    /**
     * 订单总金额减免的邮费金额
     * @Author hfh_wind
     * @param $total_price
     * @param $tmp_condition_rule
     * @return int
     */
    public function DecrRulesFee($total_price, $condition_rule)
    {
        $ruleArray = $condition_rule;
        $ruleLength = count($ruleArray);
        $discount_post_fee = 0;

//        $order_amount =[];
//        foreach ($ruleArray as $k=>$v) {
//            $order_amount[$k] = $v['order_amount'];
//        }
        $rule_order_amount=0;
        if ($total_price >= $ruleArray[$ruleLength - 1]['order_amount'] && $ruleArray[$ruleLength - 1]['status'] == 1) {
            //如果设置扣减的邮费小于设置的订单总额那么邮费也是0
            if($total_price <$ruleArray[$ruleLength - 1]['decr_post_fee']){
                $discount_post_fee = 0;
            }else{
                $discount_post_fee = $ruleArray[$ruleLength - 1]['decr_post_fee'];
                $rule_order_amount=$ruleArray[$ruleLength - 1]['order_amount'];
            }

        } elseif ($total_price < $ruleArray[0]['order_amount']) {
            $discount_post_fee = 0;

        } else {
            for ($i = 0; $i < $ruleLength - 1; $i++) {

                if ($total_price >= $ruleArray[$i]['order_amount'] && $ruleArray[$i]['order_amount'] < $ruleArray[$i + 1]['order_amount'] && $ruleArray[$i]['status'] == 1) {
                    //如果设置扣减的邮费小于设置的订单总额那么邮费也是0
                    if($total_price <$ruleArray[$i]['decr_post_fee']){
                        $discount_post_fee=0;
                    }else{
                        $discount_post_fee = $ruleArray[$i]['decr_post_fee'];
                        $rule_order_amount=$ruleArray[$i]['order_amount'];
                    }
                }
            }
        }
        if ($discount_post_fee < 0 ) {
            $discount_post_fee = 0;
        }

       $data['discount_post_fee']= $discount_post_fee; //减免邮费
       $data['rule_order_amount']= $rule_order_amount; //减免规则档次

        return $data;
    }


}
