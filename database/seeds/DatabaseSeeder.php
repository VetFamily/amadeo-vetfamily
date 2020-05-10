<?php

use Carbon\Carbon;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Initialisation de la base de données
        $this->call(TypesTableSeeder::class);
        $this->call(EspecesTableSeeder::class);
        $this->call(LaboratoiresTableSeeder::class);
        $this->call(CentralesTableSeeder::class);
        $this->call(ProduitsTableSeeder::class);
        $this->call(ProduitsTypesTableSeeder::class);
        $this->call(ProduitsEspecesTableSeeder::class);
        $this->call(CentralesProduitsTableSeeder::class);
        $this->call(CliniquesTableSeeder::class);
        $this->call(CentralesCliniquesTableSeeder::class);
        $this->call(UtilisateursTableSeeder::class);
        $this->call(AchatsMajTableSeeder::class);

        Model::reguard();
    }
}
