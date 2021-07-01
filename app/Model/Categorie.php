<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model 
{

    protected $table = 'categories';

    protected $fillable = [
        'nom', 
        'annee',
        'laboratoire_id',
        'country_id',
        'within_agreement',
        'show_in_member_reports',
        'discount_on_invoice',
        'type'
    ];

    public $timestamps = true;

    public function especes()
    {
        return $this->belongsToMany('App\Model\Espece');
    }

}