<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Clinique extends Model 
{

    protected $table = 'cliniques';

    protected $fillable = [
        'nom', 
        'veterinaires', 
        'adresse',
        'code_postal',
        'ville',
        'date_entree',
        'commentaire'
    ];

    public $timestamps = true;

    public function utilisateurs()
    {
        return $this->hasMany('Utilisateur', 'id');
    }

    public function centrales()
    {
        return $this->belongsToMany('Centrale', 'id');
    }

}