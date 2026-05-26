<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCentrisSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::create('centris_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('id_location');
            $table->string('external_contact_id')->nullable();
            $table->string('mls');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('id_location');
            $table->index('mls');
            $table->unique(['id_location', 'mls', 'external_contact_id'], 'cs_location_mls_contact_unique');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('centris_submissions');
    }
}
