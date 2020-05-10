<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Parametrage extends Model 
{

    protected $table = 'parametrage';
    public $timestamps = false;

    protected $fillable = [
        'valeur'
    ];

}