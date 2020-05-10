<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAchatsTable extends Migration {

	public function up()
	{
		Schema::create('achats', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('qte_payante_partiel')->nullable();
			$table->integer('qte_payante_complet')->nullable();
			$table->integer('qte_gratuite_partiel')->nullable();
			$table->integer('qte_gratuite_complet')->nullable();
			$table->decimal('ca_partiel', 12, 2)->nullable();
			$table->decimal('ca_complet', 12, 2)->nullable();
			$table->date('date');
			$table->integer('centrale_clinique_id')->unsigned();
			$table->integer('produit_id')->unsigned();
			$table->boolean('obsolete')->default(false);
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
			$table->index('date');
			$table->index('obsolete');
			$table->index('ca_complet');
			$table->index('qte_payante_complet');
			$table->index('produit_id');
			$table->index('centrale_clinique_id');
		});
	}

	public function down()
	{
		Schema::drop('achats');
	}
}