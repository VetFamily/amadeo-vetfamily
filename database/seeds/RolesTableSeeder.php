<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des types de produits
        DB::table('roles')->insert(['nom' => 'Administrateur']);
		DB::table('roles')->insert(['nom' => 'Laboratoire']);
		DB::table('roles')->insert(['nom' => 'Vétérinaire']);
    }
}
