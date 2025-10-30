<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'id_location',
        'ghl_access_token',
        'ghl_refresh_token',
        'ghl_token_expires_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'ghl_token_expires_at' => 'datetime',
    ];

    /**
     * Relation avec les personnes des propriétés
     */
    public function propertyPersons()
    {
        return $this->hasMany(\App\Models\PropertyPerson::class, 'id_location', 'id_location');
    }

    /**
     * Relation avec les opportunités des propriétés
     */
    public function propertyOpportunities()
    {
        return $this->hasMany(\App\Models\PropertyOpportunity::class, 'id_location', 'id_location');
    }
}
