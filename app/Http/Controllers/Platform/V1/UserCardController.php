<?php

/**
 * UserCardController
 * @Author: nlx
 * @Date:   2020-03-26 16:05:13
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-03-28 19:58:18
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Requests\Platform\UserCardRequest;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Repositories\UserCardRepository;
use ShopEM\Services\User\UserPointsService;
use Illuminate\Support\Facades\Auth;
use ShopEM\Models\YiTianUserCard;
use Illuminate\Http\Request;

class UserCardController extends BaseController 
{

    /**
     * 会员卡列表
     *
     * @Author nlx
     * @param UserCardRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,UserCardRepository $repository)
    {
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $repository->listItems($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    public function save(UserCardRequest $request)
    {
    	$data = $request->only('id','card_code','card_name','card_img','level');
    	$data['gm_id'] = $this->GMID;
    	try {
            $reCard = YiTianUserCard::where('gm_id',$this->GMID)->where(function ($query) use ($data)  {
                $query->where('level', '=' , $data['level'])
                      ->orWhere('card_name', '=', $data['card_name']);
            });
            $reCard = $reCard->first();
    		if (isset($data['id'])) 
    		{
    			if ($data['id'] <= 0 ) {
    				return $this->resFailed(406,'参数id错误');
    			}
    			$id = $data['id'];
                if ($reCard->id != $id) {
                    return $this->resFailed(701, '该卡代码/等级 已存在!');
                }
    			$card = YiTianUserCard::find($id);
	            if (empty($card)) {
	                return $this->resFailed(701, '找不到会员卡信息!');
	            }
	            $card->update($data);
    		}
            else
            {
                if (!empty($reCard)) {
                    return $this->resFailed(701, '该卡代码/等级 已存在!');
                }
            	YiTianUserCard::create($data);
            }
        } catch (\Exception $e) {

            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * 详情
     * @Author nlx
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {

        if ( $id <= 0) {
            return $this->resFailed(414);
        }

        $detail = YiTianUserCard::find($id);

        if (empty($detail))
            return $this->resFailed(700);
        
        return $this->resSuccess($detail);
    }
}