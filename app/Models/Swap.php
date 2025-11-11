<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Swap extends Model {
    use HasFactory;
    
    protected $table = 'swaps';
    
    protected $fillable = [
        'battery_moto_user_association_id', // Clé étrangère vers la table 'battery_moto_user_association'
        'battery_in_id',                    // ID de la batterie entrante
        'battery_out_id',                   // ID de la batterie sortante
        'agent_user_id',                    // ID de l'agent qui a effectué le swap (string)
        'swap_date',                        // Date du swap
        'battery_in_soc',                   // SOC batterie entrante
        'battery_out_soc',                  // SOC batterie sortante
        'swap_price',                       // Prix du swap
        'nom',                              // Nom du swappeur
        'prenom',                           // Prénom du swappeur
        'phone' ,                      // Téléphone du swappeur
        'employe_id'  , 
        'id_agence'
    ];
    
    /**
     * Définir la relation avec BatteryMotoUserAssociation
     */
    public function batteryMotoUserAssociation()
    {
        return $this->belongsTo(BatteryMotoUserAssociation::class, 'battery_moto_user_association_id');
    }
    
    /**
     * Relation avec l'utilisateur via BatteryMotoUserAssociation.
     */
    public function user()
    {
        return $this->batteryMotoUserAssociation->association->validatedUser();
    }
    
    /**
     * Relation avec le swappeur
     */
    public function swappeur()
    {
        // Le champ est défini comme string dans la migration, mais il est utilisé comme référence à UsersAgence
        // Vérifier le type de la colonne dans la base de données et ajuster si nécessaire
        return $this->belongsTo(UsersAgence::class, 'agent_user_id');
    }
    
    /**
     * Relation avec la moto via BatteryMotoUserAssociation.
     */
    public function moto()
    {
        return $this->batteryMotoUserAssociation->association->motosValide();
    }
    
    public function batteryOut()
    {
        return $this->belongsTo(BatteriesValide::class, 'battery_out_id'); // Batterie sortante
    }
    
    public function batteryIn()
    {
        return $this->belongsTo(BatteriesValide::class, 'battery_in_id'); // Batterie entrante
    }
    
    public function swapsIn() {
        return $this->hasMany(Swap::class, 'battery_in_id');
    }

    // reference à l'employé
    public function employe()
{
    return $this->belongsTo(Employe::class, 'employe_id');
}

}