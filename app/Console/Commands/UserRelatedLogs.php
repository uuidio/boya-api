<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Models\Config;
use ShopEM\Models\RelatedLogs;

class UserRelatedLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UserRelatedLogs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $conf = Config::where('group', 'platform_attrs')->first();

        $conf_value = json_decode($conf['value'], true);
        $last_day = $conf_value['platform_attrs']['last_day']??0;
        //绑定时间没设定,默认七天
        if($last_day){
            $day= -$last_day;
        }else{
            $day= -7;
        }

        $end_day = date('Y-m-d H:i:s', strtotime("$day days"));
        $goods_logs = RelatedLogs::where(['status'=>1,'hold'=>0])->where('created_at', '<=', $end_day)->get();

        if(count($goods_logs) <= 0){
            return true;
        }

//        DB::beginTransaction();
        try {

            foreach($goods_logs as $key){
                //设置过期
                RelatedLogs::where(['id'=>$key['id'],'status'=>1,'hold'=>0])->update(['status'=>0]);
            }

//            DB::commit();
        } catch (\Exception $e) {
//            DB::rollback();
            $message = $e->getMessage();
            throw new \Exception($message);
        }

        return true;
    }

}
