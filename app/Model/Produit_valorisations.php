<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Produit_valorisations extends Model 
{

    protected $table = 'produit_valorisations';

    protected $fillable = [
    	'produit_id', 
    	'valorisation1', 
    	'valorisation2',
    	'date_debut',
    	'date_fin'
    ];

    public $timestamps = true;

}