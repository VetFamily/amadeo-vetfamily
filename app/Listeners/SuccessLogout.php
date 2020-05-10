<?php

namespace App\Listeners;

use App\Model\UserLog;
use Carbon\Carbon;
use Illuminate\Auth\Events\Logout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Request;

class SuccessLogout
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
     * @param  Logout  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        // Insert logout information
        UserLog::insert([
            'user_id' => $event->user->id,
            'user_name' => $event->user->name,
            'user_ip' => Request::getClientIp(),
            'type_action' => 'LOGOUT',
            'date_action' => Carbon::now()->toDateTimeString()
        ]);
    }
}
