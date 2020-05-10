<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCentralesTable extends Migration {

	public function up()
	{
		Schema::create('centrales', function(Blueprint $table) {
			$table->increments('id');
			$table->string('nom', 255);
			$table->boolean('obsolete')->default(false);
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
		});
	}

	public function down()
	{
		Schema::drop('centrales');
	}
}