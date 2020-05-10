<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Espece extends Model 
{

    protected $table = 'especes';
    public $timestamps = true;

    public function produits()
    {
        return $this->belongsToMany('Produit', 'id');
    }

    public function categories()
    {
        return $this->belongsToMany('Categorie', 'id');
    }

}