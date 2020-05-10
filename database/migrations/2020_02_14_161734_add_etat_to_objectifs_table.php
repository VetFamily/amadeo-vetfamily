<?php

use App\Model\Obectif;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEtatToObjectifsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('objectifs', function (Blueprint $table) {
            $table->unsignedBigInteger('etat_objectif_id')->nullable();

    		$table->foreign('etat_objectif_id')->references('id')->on('etats_objectif');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('objectifs', function (Blueprint $table) {
            $table->dropForeign('etat_objectif_id');
        });
    }
}
