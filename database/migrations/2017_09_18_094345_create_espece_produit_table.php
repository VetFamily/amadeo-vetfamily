<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEspeceProduitTable extends Migration {

	public function up()
	{
		Schema::create('espece_produit', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('produit_id')->unsigned();
			$table->integer('espece_id')->unsigned();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->unique(['produit_id', 'espece_id']);
			$table->index('id');
			$table->index('produit_id');
			$table->index('espece_id');
		});
	}

	public function down()
	{
		Schema::drop('espece_produit');
	}
}