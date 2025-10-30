<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyPerson extends Model
{
    protected $table = 'property_persons';
    
    protected $fillable = [
        'id_location',
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
        return $this->belongsTo(User::class, 'id_location', 'id_location');
    }
}
