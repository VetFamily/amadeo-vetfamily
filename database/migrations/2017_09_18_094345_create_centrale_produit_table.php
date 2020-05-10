<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCentraleProduitTable extends Migration {

	public function up()
	{
		Schema::create('centrale_produit', function(Blueprint $table) {
			$table->increments('id');
			$table->string('code_produit', 60);
			$table->string('code_produit_autre', 60)->nullable();
			$table->string('code_cip', 60)->nullable();
			$table->string('denomination', 255)->nullable();
			$table->decimal('prix_unitaire_hors_promo', 12, 2)->nullable();
			$table->integer('centrale_id')->unsigned();
			$table->integer('produit_id')->unsigned();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->unique(['centrale_id', 'produit_id']);
			$table->index('id');
			$table->index('centrale_id');
			$table->index('produit_id');
		});
	}

	public function down()
	{
		Schema::drop('centrale_produit');
	}
}