<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class ValidatedUser extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $guarded = [];

    protected $fillable = [
        'user_unique_id',
        'nom',
        'prenom',
        'email',
        'phone',
        'password',
        'token',
        'numero_cni',
        'photo_cni_recto',
        'photo_cni_verso',
        'photo',
        'status',
        'link_expiration',
        'verification_code',
        'verification_code_sent_at',
        'completed_at',
        'wallet_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // Optionnel : Hachage du mot de passe
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function motos()
    {
        return $this->belongsToMany(MotosValide::class, 'association_user_motos', 'validated_user_id', 'moto_valide_id');
    }


   // public function batteries()
   // {
   //     return $this->hasManyThrough(BatteriesValide::class, MotosValide::class);
   // }


   public function batteryAssociations()
{
    return $this->hasManyThrough(
        BatteryMotoUserAssociation::class,
        AssociationUserMoto::class,
        'validated_user_id',
        'association_user_moto_id'
    );
}

    // Dans le modèle User :
    public function swaps()
    {
        return $this->hasMany(Swap::class);
    }


    public function moto()
    {
        return $this->hasOneThrough(
            \App\Models\MotosValide::class,
            \App\Models\AssociationUserMoto::class,
            'validated_user_id',   // Foreign key on association_user_motos
            'id',                  // Foreign key on motos_valides
            'id',                  // Local key on validated_users
            'moto_valide_id'       // Local key on association_user_motos
        );
    }

    public function distances()
    {
        return $this->hasMany(\App\Models\DailyDistance::class);
    }



    // liaison chauffeur et wallet 

     public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }


    /**
     * Authenticate the user with the provided credentials.
     *
     * @param array $credentials
     * @return bool
     * @throws ValidationException
     */



}
