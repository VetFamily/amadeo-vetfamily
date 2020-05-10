<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCategorieEspeceTable extends Migration {

	public function up()
	{
		Schema::create('categorie_espece', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('categorie_id')->unsigned();
			$table->integer('espece_id')->unsigned();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
			$table->index('categorie_id');
			$table->index('espece_id');
		});
	}

	public function down()
	{
		Schema::drop('categorie_espece');
	}
}