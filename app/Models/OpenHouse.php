<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenHouse extends Model
{
    protected $fillable = [
        'user_id', 'id_location', 'listing_key', 'listing_id', 'public_token',
        'starts_at', 'ends_at', 'timezone', 'address', 'property_type',
        'price_label', 'bedrooms', 'bathrooms', 'living_area', 'photos',
        'listing_url', 'broker_name', 'broker_email', 'broker_phone',
        'broker_photo_url', 'logo_url', 'note',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'photos' => 'array',
    ];

    public function registrations()
    {
        return $this->hasMany(OpenHouseRegistration::class);
    }
}
