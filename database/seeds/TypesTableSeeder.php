<?php

use Illuminate\Database\Seeder;

class TypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des types de produits
        DB::table('types')->insert(['nom' => 'Aliment']);
		DB::table('types')->insert(['nom' => 'Antibiotique']);
		DB::table('types')->insert(['nom' => 'Divers']);
		DB::table('types')->insert(['nom' => 'Matériel']);
		DB::table('types')->insert(['nom' => 'Médicament']);
    }
}
