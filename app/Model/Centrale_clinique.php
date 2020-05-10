<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Centrale_clinique extends Model 
{

    protected $table = 'centrale_clinique';

    protected $fillable = [
    	'clinique_id', 
    	'centrale_id', 
        'identifiant',
        'web'
    ];

    public $timestamps = true;

    public function produits()
    {
        return $this->belongsToMany('Produit', 'id');
    }

}