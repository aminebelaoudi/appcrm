<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyPerson extends Model
{
    protected $table = 'property_persons';
    
    protected $fillable = [
        'user_id',
        'property_listing_id',
        'contact_id',
        'name',
        'email',
        'phone',
        'implication',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id', 'id');
    }
}
