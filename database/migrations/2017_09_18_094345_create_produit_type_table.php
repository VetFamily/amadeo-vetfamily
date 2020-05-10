<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProduitTypeTable extends Migration {

	public function up()
	{
		Schema::create('produit_type', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('produit_id')->unsigned();
			$table->integer('type_id')->unsigned();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->unique(['produit_id', 'type_id']);
			$table->index('id');
			$table->index('produit_id');
			$table->index('type_id');
		});
	}

	public function down()
	{
		Schema::drop('produit_type');
	}
}