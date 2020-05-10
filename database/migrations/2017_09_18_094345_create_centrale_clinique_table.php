<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCentraleCliniqueTable extends Migration {

	public function up()
	{
		Schema::create('centrale_clinique', function(Blueprint $table) {
			$table->increments('id');
			$table->string('identifiant', 60);
			$table->integer('centrale_id')->unsigned();
			$table->integer('clinique_id')->unsigned();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->unique(['centrale_id', 'clinique_id']);
			$table->index('id');
			$table->index('clinique_id');
			$table->index('centrale_id');
		});
	}

	public function down()
	{
		Schema::drop('centrale_clinique');
	}
}