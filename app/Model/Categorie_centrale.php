<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Categorie_centrale extends Model 
{

    protected $table = 'categorie_centrale';
    public $timestamps = true;

    protected $fillable = [
        'categorie_id', 
        'centrale_id'
    ];

}