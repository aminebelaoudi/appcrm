<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyOpportunity extends Model
{
    protected $table = 'property_opportunities';
    
    protected $fillable = [
        'id_location',
        'property_listing_id',
        'opportunity_id',
        'name',
        'pipeline_id',
        'pipeline_stage_id',
        'source',
        'status',
        'monetary_value',
    ];

    protected $casts = [
        'monetary_value' => 'decimal:2',
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
