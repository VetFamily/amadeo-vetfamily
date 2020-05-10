<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Laboratoire extends Model 
{

    protected $table = 'laboratoires';
    public $timestamps = true;

    public function produits()
    {
        return $this->hasMany('Produit', 'id');
    }

}