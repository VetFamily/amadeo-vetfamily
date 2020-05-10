<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model 
{

    protected $table = 'categories';

    protected $fillable = [
        'nom', 
        'annee',
        'laboratoire_id'
    ];

    public $timestamps = true;

    public function especes()
    {
        return $this->belongsToMany('App\Model\Espece');
    }

}