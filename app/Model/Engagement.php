<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Engagement extends Model 
{

    protected $table = 'engagements';

    protected $fillable = [
        'objectif_id',
        'clinique_id',
        'valeur'
    ];

    public $timestamps = true;

}