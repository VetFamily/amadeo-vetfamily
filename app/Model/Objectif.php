<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Objectif extends Model
{

	protected $table = 'objectifs';

	protected $fillable = [
		'suivi',
		'nom',
		'categorie_id',
		'valeur',
		'type_objectif_id',
		'date_debut',
		'date_fin',
		'type_valorisation_objectif_id',
		'valorisation_laboratoire',
		'pourcentage_decote',
		'pourcentage_remise',
		'remise_additionnelle',
		'valorisation_remise',
		'objectif_conditionne_id',
		'objectif_precedent_id',
		'incrementiel',
		'valeur_ca',
		'valeur_ca_prec',
		'valeur_ca_total_prec',
		'valeur_atteinte',
		'etat_objectif_id',
		'ecart',
		'ecart_unite',
		'poids'
	];

	public $timestamps = true;

	public function getEcarts($annee, $mois_debut, $mois_fin, $requestjour = 0)
	{
		$totalJours = getNbDaysOfPeriod(intval($annee), intval($mois_debut), intval($mois_fin));
		$jour = $annee != date('Y') ? $totalJours : $requestjour - getNbDaysOfPeriod(intval($annee), 1, intval($mois_debut) - 1);
		$ecart = $this->valeur != null && $this->valeur != 0 ? round((($this->valeur_ca / $this->valeur) - ($jour / $totalJours)) * 100, 2) : null;
		$ecartUnite = $this->valeur != null && $ecart != null ? round($this->valeur * $ecart / 100, 2) : $this->valeur_ca;
		return [
			'ecart' => $ecart,
			'ecart_unite' => $ecartUnite
		];
	}
}
