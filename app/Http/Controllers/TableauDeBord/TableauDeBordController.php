<?php

namespace App\Http\Controllers\TableauDeBord;

/* composer require phpoffice/phpspreadsheet */
use App\Http\Controllers\Controller;
use App\Model\Objectif;
use App\Repositories\ObjectifRepository;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;

class TableauDeBordController extends Controller
{
	private function getNbDaysOfPeriod($year, $month1, $month2)
	{
	  $result = 0;
	  for ($i = $month1; $i <= $month2; $i++) {
	    $result += cal_days_in_month(CAL_GREGORIAN, $i, $year);
	  }

	  return $result;
	}

	public function getObjectifsGeneral(Request $request, ObjectifRepository $objectifRepository)
	{
		$dateMAJ = explode("-", Session::get('last_date'));

		$moisFin = $dateMAJ[1];
		$annee = $dateMAJ[0];
		
		// 1ère passe : Récupérations des objectifs suivis atteints et non atteints
		$listeObjectifsAtteints = $objectifRepository->findObjectifsAtteints($annee, $moisFin);
		$listeObjectifsNonAtteints = $objectifRepository->findObjectifsNonAtteints($annee, $moisFin);

		// 2e passe : Mise à jour des objectifs paliers suivis non atteints
		/*foreach ($listeObjectifsNonAtteints as $objectif) {
			// S'il s'agit d'un palier
			if ($objectif->type_objectif_id == 2)
			{
				$listeObjectifsAtteints = $this->addPalierPrecedentAtteint($objectif, $listeObjectifsAtteints);
			}
		}*/

		// 3e passe : Vérification des objectifs conditionnés pour les objectifs indiqués comme atteints
		$objectifsToDelete = [];
		$objectifsAtteintsConditionKo = [];
		foreach ($listeObjectifsAtteints as $key => $objectif) {
			if ($objectif->objectif_conditionne_id != null)
			{
				// Si l'objectif conditionné n'est pas atteint
				if (!$listeObjectifsAtteints->contains('id', $objectif->objectif_conditionne_id) && !in_array($key, $objectifsToDelete))
				{
					// Ajout de l'objectif à la liste des atteints à supprimer
					array_push($objectifsToDelete, $key);

					// Ajout de l'objectif à la liste des atteints dont la condition n'est pas atteinte
					array_push($objectifsAtteintsConditionKo, $objectif);
				} 
			}

			// S'il s'agit d'un palier
			if($objectif->type_objectif_id == 2)
			{
				// Supprimer les éventuels paliers
				$objectifsToDelete = $this->searchPalierPrecedent($objectif, $listeObjectifsAtteints, $objectifsToDelete);
			}
		}

		foreach ($objectifsToDelete as $key => $value) {
			$listeObjectifsAtteints->pull($value);
		}
			
		// 4e passe : Vérification des objectifs conditionnés
		$objectifsToDelete = [];
		foreach ($listeObjectifsAtteints as $key => $objectif) {
			if ($objectif->objectif_conditionne_id != null)
			{
				// Si l'objectif conditionné n'est pas atteint
				if (!$listeObjectifsAtteints->contains('id', $objectif->objectif_conditionne_id) && !in_array($key, $objectifsToDelete))
				{
					// Ajout de l'objectif à la liste des atteints à supprimer
					array_push($objectifsToDelete, $key);

					// Ajout de l'objectif à la liste des atteints dont la condition n'est pas atteinte
					array_push($objectifsAtteintsConditionKo, $objectif);
				} 
			}
		}

		foreach ($objectifsToDelete as $key => $value) {
			$listeObjectifsAtteints->pull($value);
		}

		$listeObjAtteints = collect([]);
		$listeObjAtteintsConditionKo = collect([]);
		$listeObjectifsSecurite = collect([]);
		$listeObjectifsLignePlus = collect([]);
		$listeObjectifsLigneMoins = collect([]);
		$listeObjectifsDanger = collect([]);

		// Parcours des objectifs atteints pour créer la liste de sortie des objectifs atteints
		foreach ($listeObjectifsAtteints as $objectif) {
			$totalJours = $this->getNbDaysOfPeriod(intval($objectif->annee), intval($objectif->mois_debut), intval($objectif->mois_fin));
			if ($objectif->annee != date('Y'))
			{
				$jour = $totalJours;
			} else 
			{
				$jour = $request->jour - $this->getNbDaysOfPeriod(intval($objectif->annee), 1, intval($objectif->mois_debut)-1);
			}
			$ecart = $objectif->valeur != null && $objectif->valeur != 0 ? round((($objectif->valeur_ca / $objectif->valeur) - ($jour / $totalJours)) * 100, 2) : null;
			$ecartEuros = $objectif->valeur != null && $ecart != null ? round($objectif->valeur * $ecart / 100, 2) : $objectif->valeur_ca;
			$objToAdd = [ $objectif->especes, $objectif->lab_nom, $objectif->nom, $objectif->valeur, $objectif->valeur_ca, $ecartEuros, $objectif->unite ];
			$listeObjAtteints->push($objToAdd);
		}

		// Parcours des objectifs atteints avec la condition KO pour créer la liste de sortie des objectifs atteints avec condition KO
		foreach ($objectifsAtteintsConditionKo as $objectif) {
			$totalJours = $this->getNbDaysOfPeriod(intval($objectif->annee), intval($objectif->mois_debut), intval($objectif->mois_fin));
			if ($objectif->annee != date('Y'))
			{
				$jour = $totalJours;
			} else 
			{
				$jour = $request->jour - $this->getNbDaysOfPeriod(intval($objectif->annee), 1, intval($objectif->mois_debut)-1);
			}
			$ecart = $objectif->valeur != null && $objectif->valeur != 0 ? round((($objectif->valeur_ca / $objectif->valeur) - ($jour / $totalJours)) * 100, 2) : null;
			$ecartEuros = $objectif->valeur != null && $ecart != null ? round($objectif->valeur * $ecart / 100, 2) : $objectif->valeur_ca;
			$objToAdd = [ $objectif->especes, $objectif->lab_nom, $objectif->nom, $objectif->valeur, $objectif->valeur_ca, $ecartEuros, $objectif->unite ];
			$listeObjAtteintsConditionKo->push($objToAdd);
		}

		// Parcours des objectifs non atteints pour créer les listes de sortie des objectifs en sécurité, en ligne ou en danger
		foreach ($listeObjectifsNonAtteints as $objectif) {
			// Calcul de l'écart : ((Avancement/Valeur)-(nb jours écoulés jusqu’à date dernière mise à jour / 365))*100
			$totalJours = $this->getNbDaysOfPeriod(intval($objectif->annee), intval($objectif->mois_debut), intval($objectif->mois_fin));
			if ($objectif->annee != date('Y'))
			{
				$jour = $totalJours;
			} else 
			{
				$jour = $request->jour - $this->getNbDaysOfPeriod(intval($objectif->annee), 1, intval($objectif->mois_debut)-1);
			}
			$ecart = $objectif->valeur != null && $objectif->valeur != 0 ? round((($objectif->valeur_ca / $objectif->valeur) - ($jour / $totalJours)) * 100, 2) : null;
			$ecartEuros = $objectif->valeur != null && $ecart != null ? round($objectif->valeur * $ecart / 100, 2) : $objectif->valeur_ca;
			$objToAdd = [ $objectif->especes, $objectif->lab_nom, $objectif->nom, $objectif->valeur, $objectif->valeur_ca, $ecartEuros, $objectif->unite ];

			if ($ecart > 10)
			{
				$listeObjectifsSecurite->push($objToAdd);
			} else if ($ecart > 0 && $ecart < 10)
			{
				$listeObjectifsLignePlus->push($objToAdd);
			} else if ($ecart > -10 && $ecart < 0)
			{
				$listeObjectifsLigneMoins->push($objToAdd);
			} else if ($ecart < -10)
			{
				$listeObjectifsDanger->push($objToAdd);
			} 
		}

		return response()->json( [ 'atteints' => $listeObjAtteints, 'atteints_condition_ko' => $listeObjAtteintsConditionKo, 'securite' => $listeObjectifsSecurite, 'ligne_plus' => $listeObjectifsLignePlus, 'ligne_moins' => $listeObjectifsLigneMoins, 'danger' => $listeObjectifsDanger ] );
	}
		
	/*
	function addPalierPrecedentAtteint($objectif, $listeObjectifsAtteints)
	{
		if ($objectif->objectif_precedent_id != null)
		{
			$objectifPrecedent = Objectif::where('id', $objectif->objectif_precedent_id)->first();
			
			if ($objectifPrecedent != null && $objectifPrecedent->valeur_atteinte && !$objectifPrecedent->suivi)
			{
				// Ajout de l'objectif dans la liste des atteints
				if (!$listeObjectifsAtteints->contains('id', $objectifPrecedent->id))
				{
					$listeObjectifsAtteints->push($objectifPrecedent);
				}
				return $listeObjectifsAtteints;
			} else
			{
				return $this->addPalierPrecedentAtteint($objectifPrecedent, $listeObjectifsAtteints);
			}
		}

		return $listeObjectifsAtteints;
	}
	*/
	
	function searchPalierPrecedent($objectif, $listeObjectifsAtteints, $objectifsToDelete)
	{
		if ($objectif->objectif_precedent_id != null)
		{
			if (!$listeObjectifsAtteints->contains('id', $objectif->objectif_precedent_id))
			{
				$objPrec = $objectif->objectif_precedent_id;
				$keyObjPrec = $listeObjectifsAtteints->search(function ($item, $key) use ($objPrec) {
					if ($item->id == $objPrec)
					{
						return $key;
					}
				});
				// Ajout de l'objectif précédent à la liste des atteints à supprimer
				if ($keyObjPrec)
				{
					array_push($objectifsToDelete, $keyObjPrec);
				}
			}

			// Récursivité
			$objectifPrecedent = Objectif::where('id', $objectif->objectif_precedent_id)->first();

			return $this->searchPalierPrecedent($objectifPrecedent, $listeObjectifsAtteints, $objectifsToDelete);
		}

		return $objectifsToDelete;
	}
	
}