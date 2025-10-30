<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGhlTokensToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'ghl_access_token')) {
                $table->text('ghl_access_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'ghl_refresh_token')) {
                $table->text('ghl_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'ghl_token_expires_at')) {
                $table->timestamp('ghl_token_expires_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ghl_access_token')) {
                $table->dropColumn('ghl_access_token');
            }
            if (Schema::hasColumn('users', 'ghl_refresh_token')) {
                $table->dropColumn('ghl_refresh_token');
            }
            if (Schema::hasColumn('users', 'ghl_token_expires_at')) {
                $table->dropColumn('ghl_token_expires_at');
            }
        });
    }
}
