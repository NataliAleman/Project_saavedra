<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'matricula',
        'nombre',
        'a_paterno',
        'a_materno',
        'contrasena',
        'perfil',
        'turno',
        'planta',
        'area',
        'puesto',
        'prod_status',
        'prod_start_at',
        'prod_locked_type',
        'prod_standard_min',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'contrasena',
        'remember_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'matricula_verified_at' => 'datetime',
        'contrasena' => 'hashed',
    ];

    public function setPasswordAttribute($value){
        $this->attributes['contrasena'] = bcrypt($value);
    }

    public function getNameAttribute()
    {
        return $this->nombre . ' ' . $this->a_paterno . ' ' . $this->a_materno;
    }
}
