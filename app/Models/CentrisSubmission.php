<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentrisSubmission extends Model
{
    protected $table = 'centris_submissions';

    protected $fillable = [
        'user_id',
        'id_location',
        'external_contact_id',
        'mls',
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id', 'id');
    }

    public function getFullNameAttribute()
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }
}
