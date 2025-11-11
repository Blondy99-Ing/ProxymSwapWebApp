<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatteryMotoUserAssociation extends Model
{
    protected $table = 'battery_moto_user_association';

    // Colonne "fillable"
    protected $fillable = [
        'association_user_moto_id',   // Clé étrangère vers la table 'association_user_motos'
        'battery_id',                 // Clé étrangère vers la table 'batteries_valides'
        'date_association',                  // Date d'échange de la batterie
    ];


  
  
    /**
     * Définir la relation avec le modèle AssociationUserMoto
     */
    public function association()
    {
        return $this->belongsTo(AssociationUserMoto::class, 'association_user_moto_id');
    }

    /**
     * Définir la relation avec le modèle BatteriesValide
     */
    public function batterie()
    {
        return $this->belongsTo(BatteriesValide::class, 'battery_id');
    }


    // Méthode pour accéder à l'utilisateur via l'association
    public function user()
    {
        return $this->association ? $this->association->validatedUser : null;
    }




}
