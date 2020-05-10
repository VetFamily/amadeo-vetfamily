<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAchatsErreursTable extends Migration {

	public function up()
	{
		Schema::create('achats_erreurs', function(Blueprint $table) {
			$table->increments('id');
			$table->string('fichier', 255);
			$table->integer('ligne')->nullable();
			$table->string('erreur', 255); // Valeurs : PRODUIT ou CLINIQUE
			$table->string('laboratoire', 255)->nullable(); // Nom du laboratoire du produit en erreur
			$table->string('code', 255); // Code produit ou code clinique
			$table->integer('centrale_id')->unsigned();
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
		});
	}

	public function down()
	{
		Schema::drop('achats_erreurs');
	}
}