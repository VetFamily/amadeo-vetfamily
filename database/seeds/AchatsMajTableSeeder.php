<?php

use Illuminate\Database\Seeder;

class AchatsMajTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('achats_maj')->insert(['centrale_id' => 1]);
        DB::table('achats_maj')->insert(['centrale_id' => 2]);
        DB::table('achats_maj')->insert(['centrale_id' => 3]);
    }
}
