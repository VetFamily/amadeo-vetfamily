<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLaboratoiresTable extends Migration {

	public function up()
	{
		Schema::create('laboratoires', function(Blueprint $table) {
			$table->increments('id');
			$table->string('nom', 255)->unique();
			$table->boolean('obsolete')->default(false);
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
			$table->index('nom');
			$table->index('obsolete');
		});
	}

	public function down()
	{
		Schema::drop('laboratoires');
	}
}