<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatteriesValide extends Model
{
    use HasFactory;

    protected $table = 'batteries_valides'; // Nom de la table correct

    protected $fillable = [
        'batterie_unique_id',
        'mac_id',
        'date_production',
        'fabriquant',
        'gps',
        'statut',
    ];




    // Relation many-to-many avec MotosValide via la table pivot 'battery_moto_user_association'
    public function motos()
    {
        return $this->belongsToMany(
            MotosValide::class,
            'battery_moto_user_association',
            'battery_id', // Clé étrangère vers BatteriesValide
            'association_user_moto_id' // Clé étrangère vers AssociationUserMoto
        )->using(AssociationUserMoto::class); // Associer avec la table intermédiaire si nécessaire
    }


    // Relation many-to-many avec les utilisateurs via la table pivot 'battery_moto_user_association'
    public function users()
    {
        return $this->belongsToMany(ValidatedUser::class, 'battery_moto_user_association');
    }


/**
 * Relation avec les entrées dans la table battery_agence
 */
public function batteryAgences()
{
    return $this->hasMany(BatteryAgence::class, 'id_battery_valide');
}

      // Relation avec BatteryEntrepot pour obtenir l'entrepôt associé à la batterie
      public function batteryEntrepots()
      {
          return $this->hasMany(BatteryEntrepot::class, 'id_battery_valide');
      }



    // Dans le modèle BatteriesValide :
public function swapsOut()
{
    return $this->hasMany(Swap::class, 'battery_out_id');
}

public function batteryMotoUserAssociations()
{
    return $this->hasMany(BatteryMotoUserAssociation::class, 'battery_id');
}


public function agences()
{
    return $this->hasMany(BatteryAgence::class, 'id_battery_valide', 'id');
}



}
