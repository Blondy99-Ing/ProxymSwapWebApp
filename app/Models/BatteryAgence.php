<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatteryAgence extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_battery_valide', 'id_agence',
    ];


    // Relation avec la batterie validée
    public function batteryValide()
    {
        return $this->belongsTo(BatteriesValide::class, 'id_battery_valide');
    }

    // Relation avec la batterie validée
    public function batteryAgences()
    {
        return $this->belongsTo(BatteryValides::class, 'id_battery_valide');
    }

    // Relation avec l'agence
    public function agence()
    {
        return $this->belongsTo(Agence::class, 'id_agence');
    }



}
