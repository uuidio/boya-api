<?php
/**
 * @Filename RoleController.php
 * @Author   swl
 * @datetime 2020-4-7
 */

namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Requests\Platform\RuleRequest;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\PlatformRule;
use ShopEM\Repositories\PlatformRuleRepository;

class RuleController extends BaseController
{

    public function __construct(PlatformRuleRepository $ruleRepository){
        parent::__construct();
        $this->ruleRepository = $ruleRepository;
    }


     /*
    * 获取规则列表
    * swl 2020-4-7
    */
     public function lists(Request $request){
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['gm_id'] = 0;

        $lists = $this->ruleRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $this->ruleRepository->ruleListField()
        ]);
     }

    /*
    * 保存规则
    * swl 2020-4-7
    */

   public function save(RuleRequest $request){

        $data = $request->only('title', 'content', 'is_show','type','listorder');
        // 查看是否已经写过这个类型的规则
        $res = PlatformRule::where(['type'=>$data['type'],'gm_id'=>0])->first();
        if(!empty($res)){
            return $this->resFailed(701, '这个规则类型已经添加过了！');           
        }
        // dd($this->GMID);
        $data['gm_id'] = 0;   
        try{
            PlatformRule::create($data);

        }catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
   }


    /*
    * 更新规则
    * swl 2020-4-7
    */

   public function update(RuleRequest $request){

        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $data = $request->only('id','title', 'content', 'is_show','type','listorder');
        // 查看是否已经写过这个类型的规则
        $res = PlatformRule::where(['type'=>$data['type'],'gm_id'=>0])->first();
        if(!empty($res) && $data['id'] != $res->id){
            return $this->resFailed(701, '这个规则类型已经添加过了！');           
        }
        $rule = PlatformRule::find($id);
        if (empty($rule)) {
            return $this->resFailed(701);
        }
        try{
            $rule->update($data);

        }catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
   }

    /**
     *  规则详情
     *
     * @Author swl
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }


        $detail = PlatformRule::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        if ($detail->gm_id != 0) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }


    /**
     * 删除规则
     * @Author swl
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {   
        $id = intval($request->id);
        
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $detail = PlatformRule::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        if ($detail->gm_id != 0) {
            return $this->resFailed(700);
        }
        
        try {
            PlatformRule::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    //获取规则类型 2020-4-9
    public function ruleType(){
        $rule = [
            // [
            //     'id' => 0,
            //     'rule'=>'积分'
            // ],
            [
                'id' => 1,
                'rule'=>'分销'
            ],

        ];
        return $this->resSuccess($rule);     
    }
}