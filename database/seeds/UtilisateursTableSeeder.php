<?php

use Illuminate\Database\Seeder;

class UtilisateursTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $id = DB::table('users')->insertGetId(['name' => 'emmeline.kemperyd@vetfamily.com', 'email' => 'emmeline.kemperyd@vetfamily.com', 'password' => bcrypt('VetFamily'), 'nom' => 'Kemperyd', 'prenom' => 'Emmeline']);
        DB::table('role_user')->insert(['user_id' => $id, 'role_id' => 1]);

        $id = DB::table('users')->insertGetId(['name' => 'anders.niva@vetfamily.com', 'email' => 'anders.niva@vetfamily.com', 'password' => bcrypt('VetFamily'), 'nom' => 'Niva', 'prenom' => 'Anders']);
        DB::table('role_user')->insert(['user_id' => $id, 'role_id' => 1]);
        
        $id = DB::table('users')->insertGetId(['name' => 'olivier.brotons@vetfamily.com', 'email' => 'olivier.brotons@vetfamily.com', 'password' => bcrypt('VetFamily'), 'nom' => 'Brotons', 'prenom' => 'Olivier']);
        DB::table('role_user')->insert(['user_id' => $id, 'role_id' => 1]);

    }
}
