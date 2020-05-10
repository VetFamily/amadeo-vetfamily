<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Centrale extends Model 
{

    protected $table = 'centrales';
    public $timestamps = true;

    public function cliniques()
    {
        return $this->belongsToMany('Clinique', 'id');
    }

    public function produits()
    {
        return $this->belongsToMany('Produit', 'id');
    }

}