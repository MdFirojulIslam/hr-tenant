<?php

namespace App\Console\Commands;

use App\Models\zkt_machine;
use App\Models\zkt_user;
use App\Models\Zkteco_Users;
use Carbon\Carbon;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Console\Command;

class FetchZktecoUser extends Command
{
    protected $signature = 'zktuser:fetch';
    protected $description = 'Fetch user data from ZKTeco K40';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $zkt_info = zkt_machine::where('Status', 1)->get();
        foreach ($zkt_info as $info) {
            $this->info($info->IP);
            $this->info($info->port);
            $zk = new ZKTeco($info->IP, $info->port); 

            if ($zk->connect()) {
                $userData = $zk->getUser();
                foreach ($userData as $user) {
                    // Check if the record already exists
                    $existingAttendance = zkt_user::where('User_Id', $user['userid'])->first();
                    if (!$existingAttendance) {
                        zkt_user::create([
                            'User_Id' => $user['userid'],
                            'Name' => $user['name'],
                            'Card_No' => $user['cardno'],
                            'Machine_Id' => $info->id,
                        ]);
                    }
                }
                $zk->disconnect();
                $this->info('User data fetched successfully.');
            } else {
                $this->error('Unable to connect to the device.');
            }
        }

       
    }
}
