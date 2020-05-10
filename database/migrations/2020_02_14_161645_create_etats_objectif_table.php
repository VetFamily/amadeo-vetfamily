<?php

use App\Model\EtatObjectif;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEtatsObjectifTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('etats_objectif', function (Blueprint $table) {
            $table->increments('id');
			$table->string('nom', 255)->index();
            $table->decimal('ecart_min', 5)->nullable();
            $table->decimal('ecart_max', 5)->nullable();
            $table->boolean('obsolete')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('etats_objectif');
    }
}
