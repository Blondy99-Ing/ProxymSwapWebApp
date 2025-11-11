<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agence extends Model
{
    use HasFactory;

    protected $fillable = [
        'agence_unique_id',
        'nom_agence',
        'nom_proprietaire',
        'ville',
        'quartier',
        'telephone',
        'email',
        'description',
        'logo',
        'energy',
        'password',
        'longitude',
        'latitude',

    ];

     // Relation avec les utilisateurs de l'agence
     public function usersAgences()
     {
         return $this->hasMany(UserAgences::class, 'id_agence');
     }
 
     // Relation avec les batteries de l'agence
     public function batteryAgences()
     {
         return $this->hasMany(BatteryAgences::class, 'id_agence');
     }
 
     // Relation avec l'historique des swaps dans l'agence
     public function historiqueAgences()
     {
         return $this->hasMany(HistoriqueAgences::class, 'id_agence');
     }
}
