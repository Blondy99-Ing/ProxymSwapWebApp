<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class Employe extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $table = 'employes';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'phone',
        'password',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get full name of the employee
     */
    public function getFullNameAttribute()
    {
        return "{$this->nom} {$this->prenom}";
    }

    /**
     * Get status of the employee (active/inactive)
     */
    public function getStatusAttribute()
    {
        return $this->deleted_at ? 'inactif' : 'actif';
    }
}