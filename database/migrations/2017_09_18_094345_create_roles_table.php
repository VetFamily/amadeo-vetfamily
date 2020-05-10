<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRolesTable extends Migration {

	public function up()
	{
		Schema::create('roles', function(Blueprint $table) {
			$table->increments('id');
			$table->string('nom', 255)->unique();
			$table->index('id');
			$table->index('nom');
		});
	}

	public function down()
	{
		Schema::drop('roles');
	}
}