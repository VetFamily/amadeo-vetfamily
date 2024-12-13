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

	public function findEstimationsRFAForExcel($startMonth, $startYear, $endMonth, $endYear, $clinic, $clinicCodes, $targetYear)
	{
		$startDate = Carbon::create($startYear, $startMonth, 1, 0, 0, 0);
		$endDate = Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth();
		
		$query = "select l.id, replace(l.nom, '''', ' ') as nom, COALESCE(calcul_periode_remises.ca_remise, 0)  as total_remises
					from laboratoires l
					join 
					(
						select lab_id, sum(ca_total)::numeric as ca_total, sum(ca_total * pourcentage_remise / 100)::numeric as ca_remise
						from (
							select lab_id, prod_id, coalesce(cpo.pourcentage_remise, 0) as pourcentage_remise,
							(CASE o.type_valorisation_objectif_id
								WHEN 1 THEN ca_total_achat
								WHEN 2 THEN ca_total_centrale
								WHEN 3 THEN ca_total_labo
								ELSE NULL
							END) AS ca_total
							from objectifs o
							join categorie_produit_objectif cpo on cpo.objectif_id = o.id
							join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
							join (
								select distinct categories.laboratoire_id as lab_id, o.id as obj_id, p.id as prod_id, a.id, a.ca_complet AS ca_total_achat, (pv.valo_euro * a.qte_payante_complet) AS ca_total_labo, (cpt.prix_unitaire_hors_promo * a.qte_payante_complet) AS ca_total_centrale
								from objectifs o
								join categories on categories.id = o.categorie_id
								join categorie_produit_objectif cpo on cpo.objectif_id = o.id
								join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
								join produits p on p.id = cpr.produit_id
								join laboratoires l on l.id = p.laboratoire_id
								left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and (a.date between '" . $startDate . "' and '" . $endDate . "') and (extract(month from a.date) between extract(month from o.date_debut) and extract(month from o.date_fin))
								join centrale_clinique cc on cc.id = a.centrale_clinique_id 
								join cliniques c on c.id = cc.clinique_id and c.country_id = categories.country_id
								left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
								left join centrale_produit cp on cp.id = a.centrale_produit_id
								left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
								where o.obsolete is false
								and o.suivi is true
								and categories.annee = '" . $targetYear . "'
								and p.invisible is false
								and cc.centrale_id != 11
								and cc.centrale_id in (select cace.centrale_id from categorie_centrale cace join objectifs o2 on o2.categorie_id = cace.categorie_id where o2.id = o.id)
								" . (($clinic == null) ? "" : ("and c.id = " . $clinic)) . "
								" . (($clinicCodes == null || in_array('0', $clinicCodes)) ? "" : ("and cc.id in (".implode(",", $clinicCodes).")")) . "
							) achats_objectifs on achats_objectifs.obj_id = o.id and cpr.produit_id = achats_objectifs.prod_id
						) achats_objectifs
						group by lab_id
					) calcul_periode_remises on calcul_periode_remises.lab_id = l.id
					where l.obsolete is false
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
							and centrale_clinique.centrale_id != 11
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
							join cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.country_id = categories.country_id
							where laboratoires.obsolete is false
							and objectifs.obsolete is false
							and produits.invisible is false
							and EXTRACT(YEAR from cliniques.date_entree) < " . ($annee+1) . "
							and categories.annee = " . $annee . "
							and achats.date between to_date('01/01/" . $annee . "', 'DD/MM/YYYY') and to_date('31/12/" . $annee . "', 'DD/MM/YYYY')
							and objectifs.obsolete is false
							and centrale_clinique.centrale_id in (select cace.centrale_id from categorie_centrale cace join objectifs o2 on o2.categorie_id = cace.categorie_id where o2.id = objectifs.id)
							and centrale_clinique.centrale_id != 11
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
		$dateFin = Carbon::create($annee, $moisFin, 1, 0, 0, 0)->endOfMonth();
		
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
							and centrale_clinique.centrale_id != 11
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
							join cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.country_id = categories.country_id
							where laboratoires.id = " . $laboratoireId . "
							and produits.invisible is false
							and cliniques.id = " . $cliniqueId . "
							and categories.annee = " . $annee . "
							and objectifs.obsolete is false
							and centrale_clinique.centrale_id in (select cace.centrale_id from categorie_centrale cace join objectifs o2 on o2.categorie_id = cace.categorie_id where o2.id = objectifs.id)
							and centrale_clinique.centrale_id != 11
						) achats
						group by lab_id
					) achats_remise on achats_remise.lab_id = laboratoires.id
					where laboratoires.id = " . $laboratoireId;

		return DB::select(DB::raw($query));
	}

	public function findByCategoryForCountryAndYear($countryId, $year)
	{
		$query = $this->laboratoire
					->select('laboratoires.id', 'laboratoires.nom', DB::raw('upper(laboratoires.nom)'))
					->join('categories', 'categories.laboratoire_id', '=', 'laboratoires.id')
					->where('laboratoires.obsolete', '=', '0')
					->where('categories.annee', '=', $year)
					->where('categories.country_id', '=', $countryId)
					->orderBy(DB::raw('upper(laboratoires.nom)'));

        return $query->distinct()->get();
	}

}