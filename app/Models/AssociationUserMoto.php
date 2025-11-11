<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssociationUserMoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'association_user_motos';

    // Indiquer que les colonnes validated_user_id et moto_valide_id sont "fillable"
    protected $fillable = ['validated_user_id', 'moto_valide_id','swap_bloque'];

    // Ajouter SoftDeletes pour gérer les suppressions logiques
    protected $dates = ['deleted_at'];
    

    protected $casts = [
        'swap_bloque' => 'boolean',
    ];

    /**
     * Définir la relation avec le modèle ValidatedUser
     */
    public function validatedUser()
    {
        return $this->belongsTo(ValidatedUser::class, 'validated_user_id');
    }

    /**
     * Définir la relation avec le modèle MotosValide
     */
    public function motosValide()
    {
        return $this->belongsTo(MotosValide::class, 'moto_valide_id');
    }

    // Relation avec la table pivot BatteryMotoUserAssociation pour suivre l'historique des échanges de batteries
    public function batteryAssociations()
    {
        return $this->hasMany(BatteryMotoUserAssociation::class, 'association_user_moto_id');
    }
}
