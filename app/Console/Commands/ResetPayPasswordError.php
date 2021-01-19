<?php

/**
 * ResetPayPassword.php
 * @Author: nlx
 * @Date:   2020-05-14 17:42:59
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-08-10 14:21:39
 */
namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Models\UserPassword;

class ResetPayPasswordError extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ResetPayPasswordError';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重置支付密码错误次数';

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
    	UserPassword::where('error_num','>',0)->update(['error_num'=>0,'status'=>1]);
    }
}