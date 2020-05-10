<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDetailAndCouleurToEtatsObjectifTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('etats_objectif', function (Blueprint $table) {
            $table->string('detail')->default('');
            $table->string('couleur')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('etats_objectif', function (Blueprint $table) {
            $table->dropColumn('detail');
            $table->dropColumn('couleur');
        });
    }
}
