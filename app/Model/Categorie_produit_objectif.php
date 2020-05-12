<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Categorie_produit_objectif extends Model 
{

    protected $table = 'categorie_produit_objectif';
    public $timestamps = true;

    protected $fillable = [
    	'categorie_produit_id', 
    	'objectif_id', 
    	'pourcentage_remise',
    	'pourcentage_remise_source'
    ];

}