<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model 
{

    protected $table = 'users_logs';

    protected $fillable = [
        'user_id', 
        'user_name',
        'user_ip',
        'type_action',
        'date_action'
    ];

    public $timestamps = false;

}