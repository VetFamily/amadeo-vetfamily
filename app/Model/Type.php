<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Type extends Model 
{

    protected $table = 'types';
    public $timestamps = true;

    public function produits()
    {
        return $this->belongsToMany('Produit', 'id');
    }

}