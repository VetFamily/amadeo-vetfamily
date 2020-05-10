<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	public function up()
	{
		Schema::create('users', function(Blueprint $table) {
			$table->increments('id');
			$table->string('name', 255)->unique();
			$table->string('email', 255);
			$table->string('password', 60);
			//$table->boolean('admin')->default(false);
			//$table->boolean('obsolete')->default(false);
			$table->integer('clinique_id')->nullable()->unsigned();
			$table->rememberToken();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->index('id');
		});
	}

	public function down()
	{
		Schema::drop('users');
	}
}