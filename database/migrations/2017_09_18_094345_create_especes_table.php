<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEspecesTable extends Migration {

	public function up()
	{
		Schema::create('especes', function(Blueprint $table) {
			$table->increments('id');
			$table->string('nom', 255)->unique();
			$table->boolean('obsolete')->default(false);
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
		});
	}

	public function down()
	{
		Schema::drop('especes');
	}
}