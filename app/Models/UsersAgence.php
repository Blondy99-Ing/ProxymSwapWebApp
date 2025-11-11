<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersAgence extends Model {
    use HasFactory;
    
    protected $fillable = [
        'user_agence_unique_id', 'nom', 'prenom', 'password', 'location', 'id_role_entite', 'id_agence',
    ];
    
    // Relation avec le rôle (un utilisateur a un rôle)
    public function role()
    {
        return $this->belongsTo(RoleEntite::class, 'id_role_entite');
    }
    
    // Relation avec l'agence (un utilisateur appartient à une agence)
    public function agence()
    {
        return $this->belongsTo(Agence::class, 'id_agence');
    }
    
    // Relation avec les swaps - Utilisation du bon nom de colonne
    public function swaps()
    {
        return $this->hasMany(Swap::class, 'agent_user_id');
    }
}