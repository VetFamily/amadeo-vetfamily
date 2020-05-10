<?php

namespace App\Listeners;

use App\Model\UserLog;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Request;

class SuccessLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        // Insert login information
        UserLog::insert([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'user_ip' => Request::getClientIp(),
            'type_action' => 'LOGIN',
            'date_action' => Carbon::now()->toDateTimeString()
        ]);
    }
}
