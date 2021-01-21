<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Country extends Model 
{

    protected $table = 'ed_country';

    /*
    * The primary key associated with the table.
    *
    * @var string
    */
   protected $primaryKey = 'ctry_id';
}