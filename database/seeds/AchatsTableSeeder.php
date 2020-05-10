<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AchatsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des achats pour Vincent : BIMEDA
        DB::table('achats')->insert(['qte_payante_complet' => '2', 'qte_gratuite_complet' => '0', 'ca_complet' => '15.84', 'date' => Carbon::now(), 'produit_id' => '519', 'centrale_clinique_id' => '8']);
        DB::table('achats')->insert(['qte_payante_complet' => '1', 'qte_gratuite_complet' => '0', 'ca_complet' => '7.16', 'date' => Carbon::now(), 'produit_id' => '495', 'centrale_clinique_id' => '8']);
        DB::table('achats')->insert(['qte_payante_complet' => '2', 'qte_gratuite_complet' => '0', 'ca_complet' => '18.52', 'date' => Carbon::now(), 'produit_id' => '526', 'centrale_clinique_id' => '8']);
        DB::table('achats')->insert(['qte_payante_complet' => '4', 'qte_gratuite_complet' => '0', 'ca_complet' => '20.94', 'date' => Carbon::now(), 'produit_id' => '525', 'centrale_clinique_id' => '8']);
        DB::table('achats')->insert(['qte_payante_complet' => '2', 'qte_gratuite_complet' => '0', 'ca_complet' => '12.98', 'date' => Carbon::now(), 'produit_id' => '521', 'centrale_clinique_id' => '8']);
        DB::table('achats')->insert(['qte_payante_complet' => '3', 'qte_gratuite_complet' => '0', 'ca_complet' => '15.35', 'date' => Carbon::now(), 'produit_id' => '513', 'centrale_clinique_id' => '8']);

        // Insertion des achats pour Lars : BIMEDA
        DB::table('achats')->insert(['qte_payante_complet' => '1', 'qte_gratuite_complet' => '0', 'ca_complet' => '7.22', 'date' => Carbon::now(), 'produit_id' => '484', 'centrale_clinique_id' => '17']);
        DB::table('achats')->insert(['qte_payante_complet' => '1', 'qte_gratuite_complet' => '0', 'ca_complet' => '6.58', 'date' => Carbon::now(), 'produit_id' => '521', 'centrale_clinique_id' => '17']);
    }
}
