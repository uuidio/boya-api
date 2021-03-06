<?php

namespace ShopEM\Console\Commands;

use Illuminate\Console\Command;
use ShopEM\Services\WebSocket\UserLogin;

class RunWSUserLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RunWSUserLogin';

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
        new UserLogin();
    }
}
