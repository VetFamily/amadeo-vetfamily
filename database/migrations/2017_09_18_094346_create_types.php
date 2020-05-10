<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTypes extends Migration {

	public function up()
	{
		\DB::unprepared("CREATE TYPE achats_sum AS (ca_periode numeric, ca_periode_prec numeric, qte_periode numeric, qte_periode_prec numeric);");
	}

	public function down()
	{
		\DB::unprepared('DROP TYPE achats_sum;');
	}
}