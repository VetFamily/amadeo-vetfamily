<?php

use Illuminate\Database\Seeder;

class CentralesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des centrales
        DB::table('centrales')->insert(['nom' => 'ALCYON']);
        DB::table('centrales')->insert(['nom' => 'CENTRAVET']);
        DB::table('centrales')->insert(['nom' => 'COVETO']);
    }
}
