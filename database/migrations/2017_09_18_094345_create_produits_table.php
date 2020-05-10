<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProduitsTable extends Migration {

	public function up()
	{
		Schema::create('produits', function(Blueprint $table) {
			$table->increments('id');
			$table->string('code_gtin', 60);
			$table->string('code_gtin_autre', 60)->nullable();
			$table->string('code_amm', 60)->nullable();
			$table->string('denomination', 255);
			$table->string('conditionnement', 255)->nullable();
			$table->decimal('prix_labo', 12, 2)->nullable();
			$table->integer('laboratoire_id')->unsigned();
			$table->boolean('obsolete')->default(false);
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
			$table->index('laboratoire_id');
			$table->index('denomination');
			$table->index('conditionnement');
			$table->index('prix_labo');
			$table->index('obsolete');
		});
	}

	public function down()
	{
		Schema::drop('produits');
	}
}