<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyPropertyPersonsUseUserId extends Migration
{
    public function up()
    {
        Schema::table('property_persons', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte de clé étrangère
            $table->dropForeign('pp_id_location_foreign');
            
            // Supprimer l'index unique qui utilise id_location
            $table->dropUnique('pp_loc_prop_contact_unique');
            
            // Supprimer l'index sur id_location
            $table->dropIndex(['id_location']);
        });
        
        Schema::table('property_persons', function (Blueprint $table) {
            // Ajouter la colonne user_id
            $table->unsignedBigInteger('user_id')->after('id')->nullable();
            
            // Créer l'index sur user_id
            $table->index('user_id');
            
            // Créer la nouvelle contrainte unique
            $table->unique(['user_id', 'property_listing_id', 'contact_id'], 'pp_user_prop_contact_unique');
            
            // Ajouter la clé étrangère vers users.id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        // Migrer les données existantes : trouver user_id basé sur id_location
        DB::statement('
            UPDATE property_persons pp
            INNER JOIN users u ON pp.id_location = u.id_location
            SET pp.user_id = u.id
            WHERE pp.user_id IS NULL
        ');
        
        Schema::table('property_persons', function (Blueprint $table) {
            // Rendre user_id non nullable après migration des données
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Supprimer l'ancienne colonne id_location
            $table->dropColumn('id_location');
        });
    }

    public function down()
    {
        Schema::table('property_persons', function (Blueprint $table) {
            // Recréer la colonne id_location
            $table->string('id_location')->after('id');
        });
        
        // Restaurer les données
        DB::statement('
            UPDATE property_persons pp
            INNER JOIN users u ON pp.user_id = u.id
            SET pp.id_location = u.id_location
        ');
        
        Schema::table('property_persons', function (Blueprint $table) {
            // Supprimer la clé étrangère user_id
            $table->dropForeign(['user_id']);
            
            // Supprimer l'index unique
            $table->dropUnique('pp_user_prop_contact_unique');
            
            // Supprimer l'index user_id
            $table->dropIndex(['user_id']);
            
            // Supprimer la colonne user_id
            $table->dropColumn('user_id');
        });
        
        Schema::table('property_persons', function (Blueprint $table) {
            // Recréer l'index id_location
            $table->index('id_location');
            
            // Recréer la contrainte unique
            $table->unique(['id_location', 'property_listing_id', 'contact_id'], 'pp_loc_prop_contact_unique');
            
            // Recréer la clé étrangère
            $table->foreign('id_location', 'pp_id_location_foreign')->references('id_location')->on('users')->onDelete('cascade');
        });
    }
}
