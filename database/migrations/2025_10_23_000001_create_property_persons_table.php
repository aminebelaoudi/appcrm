<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyPersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_persons', function (Blueprint $table) {
            $table->id();
            $table->string('id_location'); // ID location du client
            $table->string('property_listing_id'); // MLS ID de la propriété
            $table->string('contact_id'); // ID du contact GHL
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('implication')->nullable(); 
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('id_location');
            $table->index('property_listing_id');
            $table->index('contact_id');
            
            // Empêcher les doublons par client (nom court pour MySQL)
            $table->unique(['id_location', 'property_listing_id', 'contact_id'], 'pp_loc_prop_contact_unique');
            
            // Clé étrangère vers users
            $table->foreign('id_location', 'pp_id_location_foreign')->references('id_location')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('property_persons');
    }
}
