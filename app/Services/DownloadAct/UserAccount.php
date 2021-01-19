<?php
/**
 * UserAccount.php
 * @Author: nlx
 * @Date:   2020-07-06 11:00:27
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-08-04 15:58:40
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;
 
class UserAccount extends Common
{
	protected $tableName = '会员订单列表';

	public function getFilePath()
	{
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
		return $path;
	}

	public function downloadJob($data)
	{
		$id = $data['log_id'];
        $info = DB::table('download_logs')->where('id' , $id)->select('desc','gm_id','shop_id')->first();

        if(isset($info->desc) && !empty($info->desc))
        {
            $log_info = json_decode($info->desc);
            $log_info = (array)$log_info;
        }
        else
        {
            throw new \Exception('参数有误');
        }

        $select = ['user_accounts.login_account','user_accounts.mobile','user_accounts.created_at','wx_userinfos.nickname','wx_userinfos.sex','wx_userinfos.real_name','wx_userinfos.birthday','wx_userinfos.email'];

        $relModel = new \ShopEM\Models\UserRelYitianInfo;
        $model = DB::table('user_accounts')
            ->leftJoin('wx_userinfos', 'wx_userinfos.user_id','=','user_accounts.id');
            

        if (isset($log_info['from']) && !empty($log_info['from']))
        {
            $model = $model->where('user_accounts.created_at','>=' , $log_info['from']);
        }
        if (isset($log_info['to']) && !empty($log_info['to']))
        {
            $model = $model->where('user_accounts.created_at','<=' , $log_info['to']);
        }
        if (isset($log_info['gm_id']) && $log_info['gm_id']>0) 
        {
            $model = $model->leftJoin('user_rel_yitian_infos', 'user_rel_yitian_infos.user_id','=','user_accounts.id')
                    ->where('user_rel_yitian_infos.gm_id',$log_info['gm_id']);
        }
        if (isset($log_info['status']) && !empty($log_info['status']))
        {
            if ($log_info['status'] == 1)
            {
                $user_ids = $relModel->where(['new_yitian_user'=>1,'gm_id'=>$info['gm_id']])->pluck('user_id');
                $model = $model->whereIn('user_accounts.id',$user_ids);
            }
        }

        $lists = $model->select($select)->orderBy('user_accounts.created_at','asc')->get();

        if (empty($lists))
        {
        	return [];
            // throw new \Exception('找不到数据');
        }

        $filterVal = ['login_account','real_name','email','birthday','mobile','nickname','sex','created_at'];

        $exportData = []; //声明导出数据

        try {

            $lists = $lists->toArray();

            //获取下载表头
            $export_title = ['账号','真实姓名','邮箱','生日','手机号','昵称','性别','创建时间']; //表头
            // 提取导出数据
            foreach ($lists as $k => $v) 
            {
                if(is_object($v)) $v = (array)$v;

                foreach ($filterVal as $fv)
                {
                    if ($fv == 'sex' && isset($v[$fv])) 
                    {
                        $sex = $v[$fv] == 0?'未知':( $v[$fv] == 1?'男':'女');
                        $exportData[$k][$fv] = $sex;
                    }
                    elseif ($fv == 'birthday') 
                    {
                        $birthday = empty($v[$fv])?'未知':date('Y-m-d', $v[$fv]);
                        $exportData[$k][$fv] = $birthday;
                    }
                    elseif ($fv == 'nickname' && $v[$fv]) 
                    {
                        //替换emoji的代码如：
                        $nickname = json_encode($v[$fv]);
                        $nickname = preg_replace("/\\\u[ed][0-9a-f]{3}\\\u[ed][0-9a-f]{3}|\^|\=/","?",$nickname);//替换成*
                        $nickname = json_decode($nickname,1);
                        
                        $exportData[$k][$fv] = $nickname;
                    }
                    else
                    {
                        $exportData[$k][$fv] = $v[$fv] ? $v[$fv] : '';
                    }
                    
                }
            }
            array_unshift($exportData, $export_title); // 表头数据合并

        }
        catch (\Exception $e)
        {
            $message = $e->getMessage();
            throw new \Exception($message);
        }
        return $exportData;
    }
}