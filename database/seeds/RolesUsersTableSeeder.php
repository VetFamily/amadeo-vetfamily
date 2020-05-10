<?php

use Illuminate\Database\Seeder;

class RolesUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des types de produits
        DB::table('role_user')->insert(['user_id' => '1', 'role_id' => '1']);
        DB::table('role_user')->insert(['user_id' => '2', 'role_id' => '1']);
        DB::table('role_user')->insert(['user_id' => '3', 'role_id' => '1']);
        DB::table('role_user')->insert(['user_id' => '4', 'role_id' => '1']);
    }
}
