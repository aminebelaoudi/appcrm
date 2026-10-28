<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenHousesTables extends Migration
{
    public function up()
    {
        Schema::create('open_houses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('id_location');
            $table->string('listing_key');
            $table->string('listing_id');
            $table->string('public_token', 64)->unique();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone', 64);
            $table->string('address');
            $table->string('property_type')->nullable();
            $table->string('price_label')->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->string('living_area')->nullable();
            $table->json('photos');
            $table->string('listing_url', 2048)->nullable();
            $table->string('broker_name');
            $table->string('broker_email')->nullable();
            $table->string('broker_phone')->nullable();
            $table->string('broker_photo_url', 2048)->nullable();
            $table->string('logo_url', 2048)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['id_location', 'listing_key']);
        });

        Schema::create('open_house_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('open_house_id');
            $table->string('ghl_contact_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->timestamps();

            $table->foreign('open_house_id')->references('id')->on('open_houses')->onDelete('cascade');
            $table->unique(['open_house_id', 'ghl_contact_id'], 'ohr_visit_contact_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('open_house_registrations');
        Schema::dropIfExists('open_houses');
    }
}
