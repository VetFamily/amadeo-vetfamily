<?php

namespace App\Repositories;

use App\Model\Laboratoire;
use Carbon\Carbon;
use DB;

class LaboratoireRepository implements LaboratoireRepositoryInterface
{

    protected $laboratoire;

	public function __construct(Laboratoire $laboratoire)
	{
		$this->laboratoire = $laboratoire;
	}

	public function findAllForSelect()
	{
        return $this->laboratoire->select(['id', 'nom as name'])->where('obsolete', '=', '0')->orderBy('nom')->get()->toJson();
	}

	public function findAll()
	{
        return $this->laboratoire->where('obsolete', '=', '0')->orderBy('nom')->get();
	}

	public function findEstimationsRFAForExcel($moisDeb, $anneeDeb, $moisFin, $anneeFin, $clinique, $codesCliniques, $anneeObj)
	{
		$dateDeb = Carbon::create($anneeDeb, $moisDeb, 1, 0, 0, 0);
		$dateFin = Carbon::create($anneeFin, $moisFin, 28, 0, 0, 0);
		
		$query = "select laboratoires.id, replace(laboratoires.nom, '''', ' ') as nom, COALESCE(calcul_periode_remises.ca_remise, 0)  as total_remises
					from laboratoires
					join (
							select lab_id, round(sum(ca_total)::numeric, 2) as ca_total, round(sum(ca_total * pourcentage_remise / 100)::numeric, 2) as ca_remise
							from (
								select lab_id, prod_id, coalesce(categorie_produit_objectif.pourcentage_remise, 0) as pourcentage_remise,
								(CASE objectifs.type_valorisation_objectif_id
									WHEN 1 THEN ca_total_achat
									WHEN 2 THEN ca_total_centrale
									WHEN 3 THEN ca_total_labo
								 	ELSE NULL
								END) AS ca_total
								from objectifs 
								join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
								join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
								join (
									select distinct laboratoires.id as lab_id, objectifs.id as obj_id, produits.id as prod_id, achats.id, achats.ca_complet AS ca_total_achat, (produit_valorisations.valo_euro * achats.qte_payante_complet) AS ca_total_labo, (cpt.prix_unitaire_hors_promo * achats.qte_payante_complet) AS ca_total_centrale
									from objectifs
									join categories on categories.id = objectifs.categorie_id
									join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
									join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
									join produits on produits.id = categorie_produit.produit_id
									join laboratoires on laboratoires.id = produits.laboratoire_id
									left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between '" . $dateDeb . "' and '" . $dateFin . "')
									join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
									join cliniques on cliniques.id = centrale_clinique.clinique_id
									left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
									left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where objectifs.obsolete is false
									and objectifs.suivi is true
									and categories.annee = '" . $anneeObj . "'
									and produits.invisible is false
									" . (($clinique == null) ? "" : ("and cliniques.id = " . $clinique)) . "
									" . (($codesCliniques == null || in_array('0', $codesCliniques)) ? "" : ("and centrale_clinique.id in (".implode(",", $codesCliniques).")")) . "
								) achats_objectifs on achats_objectifs.obj_id = objectifs.id and categorie_produit.produit_id = achats_objectifs.prod_id
							) achats_objectifs
							group by lab_id
						) calcul_periode_remises on calcul_periode_remises.lab_id = laboratoires.id
					order By nom";

		return DB::select(DB::raw($query));
	}

	public function findBilanRFAForExcel($annee)
	{
		$query = "select laboratoires.id, replace(laboratoires.nom, '''', ' ') as nom, COALESCE(achats.ca_periode, 0) AS ca, COALESCE(achats_remise.ca_periode, 0) AS ca_remise
					from laboratoires
					left join (
						select lab_id, round(sum(ca_periode)::numeric,2) AS ca_periode
						FROM (
							select distinct laboratoires.id AS lab_id, achats.date AS date, achats.id, achats.ca_complet AS ca_periode
							from laboratoires
							join produits on laboratoires.id = produits.laboratoire_id
							join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							where laboratoires.obsolete is false
							and produits.invisible is false
							and EXTRACT(YEAR from cliniques.date_entree) < " . ($annee+1) . "
							and achats.date between to_date('01/01/" . $annee . "', 'DD/MM/YYYY') and to_date('31/12/" . $annee . "', 'DD/MM/YYYY')
						) achats
						group by lab_id
					) achats on achats.lab_id = laboratoires.id
					left join (
						select lab_id, round(sum(ca_periode)::numeric,2) AS ca_periode
						FROM (
							select distinct produits.id AS prod_id, laboratoires.id AS lab_id, achats.date AS date, achats.id, achats.ca_complet AS ca_periode
							from produits 
							join laboratoires on laboratoires.id = produits.laboratoire_id
							join categorie_produit on categorie_produit.produit_id = produits.id
							join categorie_produit_objectif on categorie_produit_objectif.categorie_produit_id = categorie_produit.id
							join objectifs on objectifs.id = categorie_produit_objectif.objectif_id
							join categories on categories.id = categorie_produit.categorie_id
							join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE 
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							where laboratoires.obsolete is false
							and objectifs.obsolete is false
							and produits.invisible is false
							and EXTRACT(YEAR from cliniques.date_entree) < " . ($annee+1) . "
							and categories.annee = " . $annee . "
							and achats.date between to_date('01/01/" . $annee . "', 'DD/MM/YYYY') and to_date('31/12/" . $annee . "', 'DD/MM/YYYY')
							and objectifs.obsolete is false
						) achats
						group by lab_id
					) achats_remise on achats_remise.lab_id = laboratoires.id
					where laboratoires.obsolete is false
					order By nom";

		return DB::select(DB::raw($query));
	}

	public function findCACliniqueById($laboratoireId, $cliniqueId, $moisDeb, $moisFin, $annee)
	{
		$dateDeb = Carbon::create($annee, $moisDeb, 1, 0, 0, 0);
		$dateFin = Carbon::create($annee, $moisFin, 28, 0, 0, 0);
		
		$query = "select laboratoires.id, COALESCE(achats.ca_periode, 0) AS ca, COALESCE(achats_remise.ca_periode, 0) AS ca_remise
					from laboratoires
					left join (
						select lab_id, round(sum(ca_periode)::numeric,2) AS ca_periode
						FROM (
							select distinct laboratoires.id AS lab_id, achats.date AS date, achats.id, achats.ca_complet AS ca_periode
							from laboratoires
							join produits on laboratoires.id = produits.laboratoire_id
							join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and achats.date between '" . $dateDeb . "' and '" . $dateFin . "'
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							where laboratoires.id = " . $laboratoireId . "
							and produits.invisible is false
							and cliniques.id = " . $cliniqueId . "
						) achats
						group by lab_id
					) achats on achats.lab_id = laboratoires.id
					left join (
						select lab_id, round(sum(ca_periode)::numeric,2) AS ca_periode
						FROM (
							select distinct produits.id AS prod_id, laboratoires.id AS lab_id, achats.date AS date, achats.id, achats.ca_complet AS ca_periode
							from produits 
							join laboratoires on laboratoires.id = produits.laboratoire_id
							join categorie_produit on categorie_produit.produit_id = produits.id
							join categorie_produit_objectif on categorie_produit_objectif.categorie_produit_id = categorie_produit.id
							join objectifs on objectifs.id = categorie_produit_objectif.objectif_id
							join categories on categories.id = categorie_produit.categorie_id
							join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and achats.date between '" . $dateDeb . "' and '" . $dateFin . "'
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							where laboratoires.id = " . $laboratoireId . "
							and produits.invisible is false
							and cliniques.id = " . $cliniqueId . "
							and categories.annee = " . $annee . "
							and objectifs.obsolete is false
						) achats
						group by lab_id
					) achats_remise on achats_remise.lab_id = laboratoires.id
					where laboratoires.id = " . $laboratoireId;

		return DB::select(DB::raw($query));
	}


}