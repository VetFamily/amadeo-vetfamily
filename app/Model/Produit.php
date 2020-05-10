<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model 
{

    protected $table = 'produits';

    protected $fillable = [
        'denomination', 
        'conditionnement',
        'code_gtin'
    ];

    public $timestamps = true;

    public function types()
    {
        return $this->belongsToMany('Type', 'id');
    }

    public function especes()
    {
        return $this->belongsToMany('Espece', 'id');
    }

    public function laboratoire()
    {
        return $this->hasOne('Laboratoire', 'id');
    }

    public function centrale_clinique()
    {
        return $this->belongsToMany('Centrale_clinique', 'id');
    }

    public function centrales()
    {
        return $this->belongsToMany('Centrale', 'id');
    }

}