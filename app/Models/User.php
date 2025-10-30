<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name',
        'id_location',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec les personnes des propriétés
     */
    public function propertyPersons()
    {
        return $this->hasMany(PropertyPerson::class, 'id_location', 'id_location');
    }

    /**
     * Relation avec les opportunités des propriétés
     */
    public function propertyOpportunities()
    {
        return $this->hasMany(PropertyOpportunity::class, 'id_location', 'id_location');
    }
}
