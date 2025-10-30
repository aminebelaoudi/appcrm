<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('id_location'); // ID location du client
            $table->string('property_listing_id'); // MLS ID de la propriété
            $table->string('opportunity_id'); // ID de l'opportunité GHL
            $table->string('name');
            $table->string('pipeline_id')->nullable();
            $table->string('pipeline_stage_id')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->nullable();
            $table->decimal('monetary_value', 15, 2)->nullable();
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('id_location');
            $table->index('property_listing_id');
            $table->index('opportunity_id');
            
            // Empêcher les doublons par client (nom court pour MySQL)
            $table->unique(['id_location', 'property_listing_id', 'opportunity_id'], 'po_loc_prop_opp_unique');
            
            // Clé étrangère vers users
            $table->foreign('id_location', 'po_id_location_foreign')->references('id_location')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('property_opportunities');
    }
}
