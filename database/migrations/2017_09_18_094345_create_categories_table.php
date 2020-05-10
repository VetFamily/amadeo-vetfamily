<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCategoriesTable extends Migration {

	public function up()
	{
		Schema::create('categories', function(Blueprint $table) {
			$table->increments('id');
			$table->string('nom', 255)->unique();
			$table->integer('laboratoire_id')->unsigned();
			$table->boolean('obsolete')->default(false);
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
			$table->index('nom');
			$table->index('laboratoire_id');
			$table->index('obsolete');
		});
	}

	public function down()
	{
		Schema::drop('categories');
	}
}