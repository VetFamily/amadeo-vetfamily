<?php

use Illuminate\Database\Seeder;

class EspecesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des espèces
        DB::table('especes')->insert(['nom' => 'Canine']);
        DB::table('especes')->insert(['nom' => 'Équine']);
        DB::table('especes')->insert(['nom' => 'Rurale']);
        DB::table('especes')->insert(['nom' => 'Autre']);
    }
}
