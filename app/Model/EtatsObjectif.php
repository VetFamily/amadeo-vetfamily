<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EtatsObjectif extends Model
{
    public $condition = null;
    protected $table = 'etats_objectif';

    protected $fillable = [
        'nom',
        'ecart_min',
        'ecart_max',
        'obsolete',
        'description'
    ];

    public $timestamps = true;
}
