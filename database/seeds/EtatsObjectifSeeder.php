<?php

use Illuminate\Database\Seeder;
use App\Model\EtatsObjectif;

class EtatsObjectifSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {

    $states = [
      ['id' => 1, 'obsolete' => FALSE, 'nom' => 'atteint', 'description' => 'Atteints', 'couleur' => '#86cd76', 'detail' => "Objectifs dont l'avancement de l'année a dépassé la cible de l'objectif."],
      ['id' => 2, 'obsolete' => FALSE, 'nom' => 'atteint_condition_ko', 'description' => 'Atteints cond non atteinte', 'couleur' => '#9cd497', 'detail' => "Objectifs dont l'avancement de l'année a dépassé la cible de l'objectif mais il existe une condition supplémentaire qui n'est pas remplie (exemple : atteinte d'un objectif chapeau)."],
      ['id' => 3, 'obsolete' => FALSE, 'nom' => 'securite', 'ecart_min' => 10, 'description' => 'En sécurité', 'couleur' => '#6896d5', 'detail' => "Objectifs dont l'avancement de l'objectif a plus de {min} % d'avance par rapport à l'avancement de l'année, mais la cible n'est pas encore atteinte."],
      ['id' => 4, 'obsolete' => FALSE, 'nom' => 'ligne_plus', 'ecart_min' => 0, 'ecart_max' => 10, 'description' => 'En ligne +', 'couleur' => '#65abb6', 'detail' => "Objectifs dont l'avancement de l'objectif a entre {min} % et {max} % d'avance par rapport à l'avancement de l'année."],
      ['id' => 5, 'obsolete' => FALSE, 'nom' => 'ligne_moins', 'ecart_min' => -10, 'ecart_max' => 0, 'description' => 'En ligne -', 'couleur' => '#dfa34e', 'detail' => "Objectifs dont l'avancement a entre {min} % et {max} % de retard par rapport à l'avancement de l'année, mais la cible n'est pas encore atteinte."],
      ['id' => 6, 'obsolete' => FALSE, 'nom' => 'danger', 'ecart_max' => -10, 'description' => 'En danger', 'couleur' => '#d76c5b', 'detail' => "Objectifs dont l'avancement de l'objectif a plus de {max} % de retard par rapport à l'avancement de l'année, mais la cible n'est pas encore atteinte."]
    ];

    foreach ($states as $state) {
      EtatsObjectif::updateOrCreate(['id' => $state['id']], $state);
    }

    /* 
        $faker = Faker\Factory::create();
        $etatIds = EtatsObjectif::all()->pluck('id')->toArray();

        $objectifs = App\Model\Objectif::all();

        foreach($objectifs as $objectif) {
            $objectif->etat_objectif_id  = $faker->randomElement($etatIds);
            $objectif->save();
        }*/
  }
}
