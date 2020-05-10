<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAchatsMajTable extends Migration {

	public function up()
	{
		Schema::create('achats_maj', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('centrale_id')->unsigned();
			$table->date('date')->nullable();
			$table->index('id');
		});
	}

	public function down()
	{
		Schema::drop('achats_maj');
	}
}