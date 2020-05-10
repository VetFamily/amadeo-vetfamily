<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRoleUserTable extends Migration {

	public function up()
	{
		Schema::create('role_user', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('role_id')->unsigned();
			$table->integer('user_id')->unsigned();
			$table->integer('clinique_id')->nullable()->unsigned();
			$table->integer('laboratoire_id')->nullable()->unsigned();
			$table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->unique(['role_id', 'user_id']);
			$table->index('id');
			$table->index('role_id');
			$table->index('user_id');
		});
	}

	public function down()
	{
		Schema::drop('role_user');
	}
}