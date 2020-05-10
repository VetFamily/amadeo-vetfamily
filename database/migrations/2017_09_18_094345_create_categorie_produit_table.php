<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCategorieProduitTable extends Migration {

	public function up()
	{
		Schema::create('categorie_produit', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('categorie_id')->unsigned();
			$table->integer('produit_id')->unsigned();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
			$table->index('categorie_id');
			$table->index('produit_id');
		});
	}

	public function down()
	{
		Schema::drop('categorie_produit');
	}
}