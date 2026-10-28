<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenHouseRegistration extends Model
{
    protected $fillable = [
        'open_house_id', 'ghl_contact_id', 'first_name', 'last_name', 'email', 'phone',
    ];

    public function openHouse()
    {
        return $this->belongsTo(OpenHouse::class);
    }
}
