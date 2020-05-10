<?php

use Illuminate\Database\Seeder;

class LaboratoiresTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des laboratoires
        DB::table('laboratoires')->insert(['nom' => 'AUDEVARD']);
        DB::table('laboratoires')->insert(['nom' => 'AXIENCE']);
        DB::table('laboratoires')->insert(['nom' => 'BAYER']);
        DB::table('laboratoires')->insert(['nom' => 'BIMEDA']);
        DB::table('laboratoires')->insert(['nom' => 'BIOVE']);
        DB::table('laboratoires')->insert(['nom' => 'BOEHRINGER']);
        DB::table('laboratoires')->insert(['nom' => 'BOIRON']);
        DB::table('laboratoires')->insert(['nom' => 'CEVA']);
        DB::table('laboratoires')->insert(['nom' => 'COOPHAVET']);
        DB::table('laboratoires')->insert(['nom' => 'DECHRA']);
        DB::table('laboratoires')->insert(['nom' => 'ELANCO']);
        DB::table('laboratoires')->insert(['nom' => 'HILL\'S']);
        DB::table('laboratoires')->insert(['nom' => 'HIPRA']);
        DB::table('laboratoires')->insert(['nom' => 'MERIAL']);
        DB::table('laboratoires')->insert(['nom' => 'MP LABO']);
        DB::table('laboratoires')->insert(['nom' => 'MSD']);
        DB::table('laboratoires')->insert(['nom' => 'NESTLE PURINA']);
        DB::table('laboratoires')->insert(['nom' => 'OSALIA']);
        DB::table('laboratoires')->insert(['nom' => 'QALIAN']);
        DB::table('laboratoires')->insert(['nom' => 'ROYAL CANIN']);
        DB::table('laboratoires')->insert(['nom' => 'SAVETIS']);
        DB::table('laboratoires')->insert(['nom' => 'TVM']);
        DB::table('laboratoires')->insert(['nom' => 'VETOQUINOL']);
        DB::table('laboratoires')->insert(['nom' => 'VIRBAC']);
        DB::table('laboratoires')->insert(['nom' => 'ZOETIS']);
    }
}
