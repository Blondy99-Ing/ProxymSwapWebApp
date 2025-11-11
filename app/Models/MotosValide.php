<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotosValide extends Model 
{
    use HasFactory;

    protected $table = 'motos_valides';

    protected $fillable = [
        'vin',
        'moto_unique_id',
        'model',
        'gps_imei',
        'assurance',
        'permis',
    ];

    // Relation avec les utilisateurs validés
    public function users()
    {
        return $this->belongsToMany(ValidatedUser::class, 'association_user_motos', 'moto_valide_id', 'validated_user_id');
    }

    // Relation avec les batteries
    public function batteries()
    {
        return $this->belongsToMany(BatteriesValide::class, 'battery_moto_user_association');
    }

    // Relation avec les échanges
    public function swaps()
    {
        return $this->hasMany(Swap::class);
    }
}