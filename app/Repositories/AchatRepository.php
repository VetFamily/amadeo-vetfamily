<?php

namespace App\Repositories;

use App\Model\Achat;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;

class AchatRepository implements AchatRepositoryInterface
{

    protected $achat;

	public function __construct(Achat $achat)
	{
		$this->achat = $achat;
	}

	/*
	* Returns all purchases
	*/
	public function findAll($startMonth, $startYear, $endMonth, $endYear, $countryId, $sourceId, $supplierId)
	{
		$params = [
			"startYear" => $startYear,
			"endYear" => $endYear,
			"startDate" => Carbon::create($startYear, $startMonth, 1, 0, 0, 0),
			"endDate" => Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth(),
			"countryId" => $countryId,
		];

		if ($sourceId != 0)
		{
			$params["sourceId"] = $sourceId;
		}

		if ($supplierId != 0)
		{
			$params["supplierId"] = $supplierId;
		}

		$query = "select distinct purr_id, c.id AS clinic_id, c.veterinaires, c.nom AS clinic_name, c.date_entree as entry_date, c.date_left, l.nom AS supplier, p.id AS product_id, CONCAT(p.denomination, ' ', p.conditionnement) AS product_name, p.code_gtin AS product_gtin, liste_types.types, liste_especes.especes as species, ft.classe1_code, ft.classe1_nom, ft.classe2_code, ft.classe2_nom, ft.classe3_code, ft.classe3_nom, purc_date, purc_paid_unit, purc_free_unit, purc_gross, purc_net, ce.nom AS centrale, liste_categories.categories, purc_currency, purc_clinic_rebate_percent, purc_clinic_rebate, purc_clinic_rebate_amadeo, purc_central_rebate_percent, purc_central_rebate, purc_central_rebate_amadeo, purc_double_net, purc_valorization, prbc_brand_level1, prbc_brand_level2, catt_category1, catt_category2, catt_category3, catt_indication,purr_product_code, purr_product_name, purr_product_supplier, purr_product_category1, purr_product_category2, purr_product_category3
				from ed_purchases_ref purr
				join ed_purchase purc on purc_purchase_ref_id = purr_id
				join produits p on p.id = purr_product_id
				left join familles_therapeutiques ft on ft.id = p.famille_therapeutique_id
				join laboratoires l on l.id = p.laboratoire_id
				join centrale_clinique cc on cc.id = purr_source_clinic_id
				join centrales ce on ce.id = cc.centrale_id
				join cliniques c on c.id = cc.clinique_id
				left join
				(
					select produit_type.produit_id, string_agg(types.nom, ', ' order by types.nom) AS types
					from types
					join produit_type ON types.id = produit_type.type_id
					group by produit_type.produit_id
				) liste_types on liste_types.produit_id = p.id
				left join
				(
					select espece_produit.produit_id, string_agg(especes.nom, ', ' order by especes.nom) AS especes
					from especes
					join espece_produit ON especes.id = espece_produit.espece_id
					group by espece_produit.produit_id
				) liste_especes on liste_especes.produit_id = p.id
				left join 
				(
					select distinct cp.produit_id, string_agg(cat.nom, ', ' order by cat.nom) as categories 
					from categories cat 
					join categorie_produit cp on cp.categorie_id = cat.id 
					where cat.annee in (:startYear, :endYear)
					and cat.country_id = :countryId
					group by cp.produit_id
				) liste_categories on liste_categories.produit_id = p.id
				left join ed_product_vf_brand_category on prbc_product_id = p.id
				left join ed_vf_category_tree on catt_id = prbc_category_tree_id
				where " . ($supplierId != 0 ? "l.id = :supplierId " : "l.obsolete is false") . "
				and purr_obsolete IS FALSE
				and ((purc_paid_unit != 0) or (purc_free_unit != 0) or ((purc_gross != 0) or (purc_net != 0)))
				and p.invisible IS FALSE
				and c.obsolete IS FALSE
				and purc_date between :startDate and :endDate
				and c.country_id = :countryId
				" . ($sourceId != 0 ? "and cc.centrale_id = :sourceId " : "") . "
				order by purc_date, clinic_name, supplier, product_name, centrale";
			
		$result = DB::select(DB::raw($query), $params);

		return $result;
	}

	/*
	* Returns all purchases for product view filtered by params : period, display type (by year or by month), clinics, products, valorization, central purchasing.
	*/
	public function findAllByProducts($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff)
	{
		$startDate = Carbon::create($startYear, $startMonth, 1, 0, 0, 0);
		$endDate = Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth();
		$nbYearDiff = ($startYear != $endYear) ? ($endYear - $startYear) : 1;
		$startDatePrec = Carbon::create((int)$startYear-$nbYearDiff, $startMonth, 1, 0, 0, 0);
	  	$endDatePrec = Carbon::create((int)$endYear-$nbYearDiff, $endMonth, 1, 0, 0, 0)->endOfMonth();

		switch ($valorization) {
			case 1:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "0";
				$groupByValoProduit = "";
				break;
			case 2:
				$selectQuery = "(pv.valo_euro * a.qte_payante_complet)";
				$joinQuery = " left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))";
				$selectValoQuery = "(CASE WHEN count(pv.valo_euro) > 0 THEN 0 ELSE 1 END)";
				$groupByValoProduit = "group by prod_id, p.denomination, p.conditionnement, a.id, a.date, pv.valo_euro";
				break;
			case 3:
				$selectQuery = "(cpt.prix_unitaire_hors_promo * a.qte_payante_complet)";
				$joinQuery = " left join centrale_produit cp on cp.id = a.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1";
				$selectValoQuery = "(CASE WHEN count(cpt.prix_unitaire_hors_promo) > 0 THEN 0 ELSE 1 END)";
				$groupByValoProduit = "group by prod_id, p.denomination, p.conditionnement, a.id, a.date, cpt.prix_unitaire_hors_promo";
				break;
			default:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "false";
				$groupByValoProduit = "";
				break;
		}

  		if ($byYear)
  		{
  			// Complete year
  			$query = "select distinct p.id AS prod_id, p.denomination, p.conditionnement, p.code_gtin, p.obsolete, l.nom as laboratoire, liste_types.types, liste_especes.especes,
					coalesce(calcul_periode.ca_periode,0) AS ca_periode, 
					coalesce(calcul_periode.qte_periode,0) AS qte_periode, 
					coalesce(calcul_periode.ca_periode_prec,0) AS ca_periode_prec, 
					coalesce(calcul_periode.qte_periode_prec,0) AS qte_periode_prec,
					coalesce(calcul_periode.manque, false) as manque_periode,
					coalesce(calcul_periode.manque_prec, false) as manque_periode_prec
					FROM produits p
					join laboratoires l on l.id = p.laboratoire_id
    				join
    				(
    					select produit_type.produit_id, string_agg(types.nom, ', ' order by types.nom) AS types
					    from types
					    join produit_type ON types.id = produit_type.type_id
					    group by produit_type.produit_id
					) liste_types on liste_types.produit_id = p.id
					join
					(
						select espece_produit.produit_id, string_agg(especes.nom, ', ' order by especes.nom) AS especes
					    from especes
					    join espece_produit ON especes.id = espece_produit.espece_id
					    group by espece_produit.produit_id
					) liste_especes on liste_especes.produit_id = p.id
					LEFT JOIN (
						select prod_id, 
						(case when sum(manque) > 0 then true else false end) as manque, (case when sum(manque_prec) > 0 then true else false end) as manque_prec,
						round(sum(ca_periode)::numeric,2) AS ca_periode,
						round(sum(ca_periode_prec)::numeric,2) AS ca_periode_prec,
						round(sum(qte_periode)) AS qte_periode,
						round(sum(qte_periode_prec)) AS qte_periode_prec
						FROM (
							select" . ($groupByValoProduit != "" ? "" : " distinct") . " p.id AS prod_id, a.id, a.date, 
							(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as manque,
							(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as manque_prec,
							(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
							(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec,
							(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.qte_payante_complet else 0 end) as qte_periode, 
							(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then a.qte_payante_complet else 0 end) as qte_periode_prec
							from produits p
							left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
							join centrale_clinique cc on cc .id = a.centrale_clinique_id 
							" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
							" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
							" . $joinQuery . "
							where p.invisible is false
							" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
							" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "") . "
							" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
							" . $groupByValoProduit . "
						) achats_periode
						group by prod_id
					) calcul_periode ON calcul_periode.prod_id = p.id
					where p.invisible is false
					" . ($products != null ? "and p.id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
					order by laboratoire, denomination, conditionnement";
  		} else 
  		{
  			// Detail by month
	  		$querySelect = "select id, laboratoire, denomination, conditionnement, code_gtin, types, especes, obsolete ";
	  		$queryCrosstabAs = "
				) AS t (id text, laboratoire text, denomination text, conditionnement text, code_gtin text, types text, especes text, obsolete boolean";
	  		
  			for($i=0 ; $i<$nbMonthDiff ; $i++)
  			{
  				$querySelect .= "
  				, coalesce((m" . $i . ").ca_periode,0) AS ca_periode_M" . $i . ", coalesce((m" . $i . ").qte_periode,0) AS qte_periode_M" . $i . ", coalesce((m" . $i . ").ca_periode_prec,0) AS ca_periode_prec_M" . $i . ", coalesce((m" . $i . ").qte_periode_prec,0) AS qte_periode_prec_M" . $i . ", (m" . $i . ").manque_periode AS manque_periode_M" . $i . ", (m" . $i . ").manque_periode_prec AS manque_periode_prec_M" . $i;

  				$queryCrosstabAs .= "
  				, m" . $i . " achats_sum";
  			}
  			$queryCrosstabAs .= ")";

	  		$queryFrom = "
		  		FROM crosstab(
					\$\$select distinct produits_mois.prod_id, produits_mois.laboratoire, produits_mois.denomination, 
						produits_mois.conditionnement, produits_mois.code_gtin, produits_mois.types, produits_mois.especes, produits_mois.obsolete, produits_mois.mois, 
						(calcul_periode.ca_periode, 
						calcul_periode.ca_periode_prec, 
						calcul_periode.qte_periode, 
						calcul_periode.qte_periode_prec, 
						coalesce(calcul_periode.manque, false),  
						coalesce(calcul_periode.manque_prec, false))::achats_sum
					FROM 
					(
						SELECT distinct p.id AS prod_id, p.denomination, p.conditionnement, p.code_gtin, p.obsolete, l.nom as laboratoire, liste_types.types, liste_especes.especes, liste_mois.g_date::date AS mois 
                        FROM produits p
						join laboratoires l on l.id = p.laboratoire_id
        				join
        				(
        					select produit_type.produit_id, string_agg(types.nom, ', ' order by types.nom) AS types
						    from types
						    join produit_type ON types.id = produit_type.type_id
						    group by produit_type.produit_id
						) liste_types on liste_types.produit_id = p.id
						join
						(
							select espece_produit.produit_id, string_agg(especes.nom, ', ' order by especes.nom) AS especes
						    from especes
						    join espece_produit ON especes.id = espece_produit.espece_id
						    group by espece_produit.produit_id
						) liste_especes on liste_especes.produit_id = p.id,
                    	(
							select g_date, (case when extract(month from g_date) < 10 then '0' else '' end || extract(month from g_date) || '-' || extract(year from g_date)) as mois 
							from generate_series('" . $startDate . "'::timestamp, '" . $endDate . "'::timestamp, '1 month') as g(g_date)
						) liste_mois
                    	where p.invisible is false
						" . ($products != null ? "and p.id in (" . implode(",", $products) . ")" : "") . "
					) produits_mois
                    LEFT JOIN (
						select prod_id, mois, 
						(case when sum(manque) > 0 then true else false end) as manque, (case when sum(manque_prec) > 0 then true else false end) as manque_prec,
						round(sum(ca_periode)::numeric,2) AS ca_periode,
						round(sum(ca_periode_prec)::numeric,2) AS ca_periode_prec,
						round(sum(qte_periode)) AS qte_periode,
						round(sum(qte_periode_prec)) AS qte_periode_prec
						FROM
						(
							select" . ($groupByValoProduit != "" ? "" : " distinct") . " p.id AS prod_id, a.id, 
							(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.date else (a.date + interval '1 year') end) as mois, 
							(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as manque,
							(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as manque_prec,
							(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
							(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec,
							(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.qte_payante_complet else 0 end) as qte_periode, 
							(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then a.qte_payante_complet else 0 end) as qte_periode_prec
							from produits p
							left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
							join centrale_clinique cc on cc .id = a.centrale_clinique_id 
							" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
							" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
							" . $joinQuery . "
							where p.invisible is false
							" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
							" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
							" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
							" . $groupByValoProduit . "
						) achats_periode
					    group by prod_id, mois
					) calcul_periode ON calcul_periode.mois = produits_mois.mois AND calcul_periode.prod_id = produits_mois.prod_id
					order by laboratoire, denomination, conditionnement\$\$
					, 'select g_date::date as mois from generate_series(''" . $startDate . "''::timestamp, ''" . $endDate . "''::timestamp, ''1 month'') as g(g_date) order by mois'";
			
  			$query = $querySelect . $queryFrom . $queryCrosstabAs;
  		}

	  	$result = DB::select(DB::raw($query));

        return $result;
	}

	/*
	* Returns all purchases for laboratory view filtered by params : period, display type (by year or by month), clinics, products, valorization, central purchasing.
	*/
	public function findAllByLaboratories($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff)
	{
		$startDate = Carbon::create($startYear, $startMonth, 1, 0, 0, 0);
		$endDate = Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth();
		$nbYearDiff = ($startYear != $endYear) ? ($endYear - $startYear) : 1;
		$startDatePrec = Carbon::create((int)$startYear-$nbYearDiff, $startMonth, 1, 0, 0, 0);
	  	$endDatePrec = Carbon::create((int)$endYear-$nbYearDiff, $endMonth, 1, 0, 0, 0)->endOfMonth();

		switch ($valorization) {
			case 1:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "1";
				$groupByValoLabo = "";
				break;
			case 2:
				$selectQuery = "(pv.valo_euro * a.qte_payante_complet)";
				$joinQuery = " left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))";
				$selectValoQuery = "(CASE WHEN count(pv.valo_euro) > 0 THEN 1 ELSE 0 END)";
				$groupByValoLabo = "group by lab_id, a.produit_id, a.id, a.date, pv.valo_euro";
				break;
			case 3:
				$selectQuery = "(cpt.prix_unitaire_hors_promo * a.qte_payante_complet)";
				$joinQuery = " left join centrale_produit cp on cp.id = a.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1";
				$selectValoQuery = "(CASE WHEN count(cpt.prix_unitaire_hors_promo) > 0 THEN 1 ELSE 0 END)";
				$groupByValoLabo = "group by lab_id, a.produit_id, a.id, a.date, cpt.prix_unitaire_hors_promo";
				break;
			default:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "1";
				$groupByValoLabo = "";
				break;
		}

  		if ($byYear)
  		{
  			// Complete year
	  		$query = "select lab_id, laboratoire, manque_periode, manque_periode_prec, ca_periode, ca_periode_prec
					from
					(
						(select distinct l.id AS lab_id, l.nom AS laboratoire, 
						(case when calcul_periode.nb_produits != calcul_periode.nb_valos then true else false end) as manque_periode, 
						(case when calcul_periode.nb_produits_prec != calcul_periode.nb_valos_prec then true else false end) as manque_periode_prec, 
						coalesce(calcul_periode.ca_periode, 0) as ca_periode, 
						coalesce(calcul_periode.ca_periode_prec, 0) AS ca_periode_prec, 1 as n
						from laboratoires l
						left join 
						(
							select lab_id, sum(nb_produits) as nb_produits, sum(nb_produits_prec) as nb_produits_prec, 
							sum(nb_valos) as nb_valos, sum(nb_valos_prec) as nb_valos_prec, 
							round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select" . ($groupByValoLabo != "" ? "" : " distinct") . " p.laboratoire_id as lab_id, a.produit_id, a.id, a.date, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then 1 else 0 end) as nb_produits,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then 1 else 0 end) as nb_produits_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as nb_valos,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as nb_valos_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec
								from achats a
								join produits p on p.id = a.produit_id
								join centrale_clinique cc on cc .id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
								" . $joinQuery . "
								where a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
								" . $groupByValoLabo . "
							) achats
							group by lab_id
						) calcul_periode on calcul_periode.lab_id = l.id
						" . ($products != null ? "join produits p on p.laboratoire_id = l.id and p.id in (" . implode(",", $products) . ")" : "") . "
						where l.obsolete is false)
						
						" . ($products == null ? "union
						
						(select distinct l.id AS lab_id, l.nom AS laboratoire, false as manque_periode, false as manque_periode_prec, coalesce(liste_achats.ca_periode,0) as ca_periode, coalesce(liste_achats.ca_periode_prec,0) AS ca_periode_prec, 2 as n
						from laboratoires l,
						(
							select round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select a.produit_id, a.date, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.ca_complet else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then a.ca_complet else 0 end) as ca_periode_prec
								from achats_autres a
								join centrale_clinique cc on cc.id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								where ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
							) achats
						) liste_achats
						where l.id = 40)" : "") . "
					) t
					order by n, laboratoire";
						
  		} else 
  		{
  			// Detail by month
	  		$querySelect = "select lab_id, laboratoire ";
	  		$queryCrosstabAs = "
				) AS t (lab_id text, laboratoire text";
	  		
  			for($i=0 ; $i<$nbMonthDiff ; $i++)
  			{
  				$querySelect .= "
  				, coalesce((m" . $i . ").ca_periode,0) AS ca_periode_M" . $i . ", coalesce((m" . $i . ").ca_periode_prec,0) AS ca_periode_prec_M" . $i . ", (m" . $i . ").manque_periode AS manque_periode_M" . $i . ", (m" . $i . ").manque_periode_prec AS manque_periode_prec_M" . $i;

  				$queryCrosstabAs .= "
  				, m" . $i . " achats_sum";
  			}
  			$queryCrosstabAs .= ")";

	  		$queryFrom = "
		  		FROM crosstab(
					\$\$select lab_id, laboratoire, mois, achats
					from (
						(select distinct labos_mois.lab_id, labos_mois.lab_nom AS laboratoire, labos_mois.mois AS mois
						, (calcul_periode.ca_periode, calcul_periode.ca_periode_prec, 0, 0, (case when calcul_periode.nb_produits != calcul_periode.nb_valos then true else false end), (case when calcul_periode.nb_produits_prec != calcul_periode.nb_valos_prec then true else false end))::achats_sum as achats, 1 as n
						FROM 
						(
							SELECT distinct l.id AS lab_id, l.nom AS lab_nom, liste_mois.g_date::date AS mois 
					        FROM laboratoires l
							" . ($products != null ? "join produits p on p.laboratoire_id = l.id and p.id in (" . implode(",", $products) . ")" : "") . ",
					    	(
								select g_date, (case when extract(month from g_date) < 10 then '0' else '' end || extract(month from g_date) || '-' || extract(year from g_date)) as mois 
								from generate_series('" . $startDate . "'::timestamp, '" . $endDate . "'::timestamp, '1 month') as g(g_date)
							) liste_mois
					    	where l.obsolete is false
						) labos_mois
					    LEFT JOIN (
							select lab_id, mois, sum(nb_produits) as nb_produits, sum(nb_produits_prec) as nb_produits_prec, 
							sum(nb_valos) as nb_valos, sum(nb_valos_prec) as nb_valos_prec, 
							round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select" . ($groupByValoLabo != "" ? "" : " distinct") . " p.laboratoire_id as lab_id, a.produit_id, a.id, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.date else (a.date + interval '1 year') end) as mois, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then 1 else 0 end) as nb_produits,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then 1 else 0 end) as nb_produits_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as nb_valos,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as nb_valos_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec
								from achats a
								join produits p on p.id = a.produit_id
								join centrale_clinique cc on cc .id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
								" . $joinQuery . "
								where a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
								" . $groupByValoLabo . "
							) achats
							group by lab_id, mois
						) calcul_periode on calcul_periode.lab_id = labos_mois.lab_id and calcul_periode.mois = labos_mois.mois)
					
						" . ($products == null ? "union 
					
						(select distinct labos_mois.lab_id, labos_mois.lab_nom AS laboratoire, labos_mois.mois AS mois
						, (calcul_periode.ca_periode, calcul_periode.ca_periode_prec, 0, 0, false, false)::achats_sum as achats, 2 as n
						FROM 
						(
							SELECT distinct l.id AS lab_id, l.nom AS lab_nom, liste_mois.g_date::date AS mois 
					        FROM laboratoires l,
					    	(
								select g_date, (case when extract(month from g_date) < 10 then '0' else '' end || extract(month from g_date) || '-' || extract(year from g_date)) as mois 
								from generate_series('" . $startDate . "'::timestamp, '" . $endDate . "'::timestamp, '1 month') as g(g_date)
							) liste_mois
					    	where l.id = 40
						) labos_mois
					    LEFT JOIN (
							select mois, round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select a.produit_id, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.date else (a.date + interval '1 year') end) as mois,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.ca_complet else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then a.ca_complet else 0 end) as ca_periode_prec
								from achats_autres a
								join centrale_clinique cc on cc.id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								where ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
							) achats
							group by mois
						) calcul_periode on calcul_periode.mois = labos_mois.mois)" : "") . "
					) t
					order by n, laboratoire\$\$
					, 'select g_date::date as mois from generate_series(''" . $startDate . "''::timestamp, ''" . $endDate . "''::timestamp, ''1 month'') as g(g_date) order by mois'";
			
  			$query = $querySelect . $queryFrom . $queryCrosstabAs;
		  }
	  	$result = DB::select(DB::raw($query));

        return $result;
	}

	/*
	* Returns all purchases for clinic view filtered by params : period, display type (by year or by month), clinics, products, valorization, central purchasing.
	*/
	public function findAllByClinics($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff)
	{
		$startDate = Carbon::create($startYear, $startMonth, 1, 0, 0, 0);
		$endDate = Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth();
		$nbYearDiff = ($startYear != $endYear) ? ($endYear - $startYear) : 1;
		$startDatePrec = Carbon::create((int)$startYear-$nbYearDiff, $startMonth, 1, 0, 0, 0);
	  	$endDatePrec = Carbon::create((int)$endYear-$nbYearDiff, $endMonth, 1, 0, 0, 0)->endOfMonth();

		switch ($valorization) {
			case 1:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "1";
				$groupByValo = "";
				break;
			case 2:
				$selectQuery = "(pv.valo_euro * a.qte_payante_complet)";
				$joinQuery = " left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))";
				$selectValoQuery = "(CASE WHEN count(pv.valo_euro) > 0 THEN 1 ELSE 0 END)";
				$groupByValo = "group by clinique_id, a.produit_id, a.id, a.date, pv.valo_euro";
				break;
			case 3:
				$selectQuery = "(cpt.prix_unitaire_hors_promo * a.qte_payante_complet)";
				$joinQuery = " left join centrale_produit cp on cp.id = a.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1";
				$selectValoQuery = "(CASE WHEN count(cpt.prix_unitaire_hors_promo) > 0 THEN 1 ELSE 0 END)";
				$groupByValo = "group by clinique_id, a.produit_id, a.id, a.date, cpt.prix_unitaire_hors_promo";
				break;
			default:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "1";
				$groupByValo = "";
				break;
		}

  		if ($byYear)
  		{
  			// Complete year
			$query = "select clinique_id, veterinaires, clinique, 
					  manque_periode, manque_periode_prec, ca_periode, ca_periode_prec
					from
					(
						select distinct c.id AS clinique_id, c.veterinaires, c.nom AS clinique, 
						(case when calcul_periode.nb_produits != calcul_periode.nb_valos then true else false end) as manque_periode, 
						(case when calcul_periode.nb_produits_prec != calcul_periode.nb_valos_prec then true else false end) as manque_periode_prec, 
						coalesce(calcul_periode.ca_periode, 0) as ca_periode, 
						coalesce(calcul_periode.ca_periode_prec, 0) AS ca_periode_prec
						from cliniques c
						left join 
						(
							select clinique_id, 
							sum(nb_produits) as nb_produits, sum(nb_produits_prec) as nb_produits_prec, 
							sum(nb_valos) as nb_valos, sum(nb_valos_prec) as nb_valos_prec, 
							round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select" . ($groupByValo != "" ? "" : " distinct") . " cc.clinique_id, a.produit_id, a.id, a.date, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then 1 else 0 end) as nb_produits,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then 1 else 0 end) as nb_produits_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as nb_valos,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as nb_valos_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec
								from achats a
								join produits p on p.id = a.produit_id
								join centrale_clinique cc on cc .id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
								" . $joinQuery . "
								where a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
								" . $groupByValo . "
							) achats
							group by clinique_id
						) calcul_periode on calcul_periode.clinique_id = c.id
						where " . ($clinics != null ? "c.id in (" . implode(",", $clinics) . ")" : "c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
					) t
					order by veterinaires";
						
  		} else 
  		{
  			// Recherche par mois
	  		$querySelect = "select clinique_id, veterinaires, clinique ";
	  		$queryCrosstabAs = "
				) AS t (clinique_id text, veterinaires text, clinique text";
	  		
  			for($i=0 ; $i<$nbMonthDiff ; $i++)
  			{
  				$querySelect .= "
  				, coalesce((m" . $i . ").ca_periode,0) AS ca_periode_M" . $i . ", coalesce((m" . $i . ").ca_periode_prec,0) AS ca_periode_prec_M" . $i . ", (m" . $i . ").manque_periode AS manque_periode_M" . $i . ", (m" . $i . ").manque_periode_prec AS manque_periode_prec_M" . $i;

  				$queryCrosstabAs .= "
  				, m" . $i . " achats_sum";
  			}
  			$queryCrosstabAs .= ")";

	  		$queryFrom = "
		  		FROM crosstab(
					\$\$select clinique_id, veterinaires, clinique, mois, achats
					from (
						select distinct cliniques_mois.clinique_id, cliniques_mois.veterinaires, cliniques_mois.clinique AS clinique, cliniques_mois.mois AS mois
						, (calcul_periode.ca_periode, calcul_periode.ca_periode_prec, 0, 0, (case when calcul_periode.nb_produits != calcul_periode.nb_valos then true else false end), (case when calcul_periode.nb_produits_prec != calcul_periode.nb_valos_prec then true else false end))::achats_sum as achats
						FROM 
						(
							SELECT distinct c.id AS clinique_id, c.veterinaires, c.nom AS clinique, liste_mois.g_date::date AS mois 
					        FROM cliniques c,
					    	(
								select g_date, (case when extract(month from g_date) < 10 then '0' else '' end || extract(month from g_date) || '-' || extract(year from g_date)) as mois 
								from generate_series('" . $startDate . "'::timestamp, '" . $endDate . "'::timestamp, '1 month') as g(g_date)
							) liste_mois
					    	where " . ($clinics != null ? "c.id in (" . implode(",", $clinics) . ")" : "c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
						) cliniques_mois
					    LEFT JOIN (
							select clinique_id, mois, 
							sum(nb_produits) as nb_produits, sum(nb_produits_prec) as nb_produits_prec, 
							sum(nb_valos) as nb_valos, sum(nb_valos_prec) as nb_valos_prec, 
							round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select" . ($groupByValo != "" ? "" : " distinct") . " cc.clinique_id, a.produit_id, a.id, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.date else (a.date + interval '1 year') end) as mois, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then 1 else 0 end) as nb_produits,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then 1 else 0 end) as nb_produits_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as nb_valos,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as nb_valos_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec
								from achats a
								join produits p on p.id = a.produit_id
								join centrale_clinique cc on cc .id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
								" . $joinQuery . "
								where a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
								" . $groupByValo . "
							) achats
							group by clinique_id, mois
						) calcul_periode on calcul_periode.clinique_id = cliniques_mois.clinique_id and calcul_periode.mois = cliniques_mois.mois
					) t
					order by veterinaires\$\$
					, 'select g_date::date as mois from generate_series(''" . $startDate . "''::timestamp, ''" . $endDate . "''::timestamp, ''1 month'') as g(g_date) order by mois'";
			
  			$query = $querySelect . $queryFrom . $queryCrosstabAs;
  		}
	  	
	  	$result = DB::select(DB::raw($query));

        return $result;
	}

	/*
	* Returns all purchases for category view filtered by params : period, display type (by year or by month), clinics, products, valorization, central purchasing.
	*/
	public function findAllByCategories($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff)
	{
		$startDate = Carbon::create($startYear, $startMonth, 1, 0, 0, 0);
		$endDate = Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth();
		$nbYearDiff = ($startYear != $endYear) ? ($endYear - $startYear) : 1;
		$startDatePrec = Carbon::create((int)$startYear-$nbYearDiff, $startMonth, 1, 0, 0, 0);
	  	$endDatePrec = Carbon::create((int)$endYear-$nbYearDiff, $endMonth, 1, 0, 0, 0)->endOfMonth();

		if ((sizeof(Auth::user()->roles) >0) && ("Vétérinaire" == Auth::user()->roles[0]['nom']))
		{
			$clinics = [Session::get('user_clinique_id')];
		}
	
		switch ($valorization) {
			case 1:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "1";
				$groupByValo = "";
				break;
			case 2:
				$selectQuery = "(pv.valo_euro * a.qte_payante_complet)";
				$joinQuery = " left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))";
				$selectValoQuery = "(CASE WHEN count(pv.valo_euro) > 0 THEN 1 ELSE 0 END)";
				$groupByValo = "group by categorie_id, a.produit_id, a.id, a.date, pv.valo_euro";
				break;
			case 3:
				$selectQuery = "(cpt.prix_unitaire_hors_promo * a.qte_payante_complet)";
				$joinQuery = " left join centrale_produit cp on cp.id = a.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1";
				$selectValoQuery = "(CASE WHEN count(cpt.prix_unitaire_hors_promo) > 0 THEN 1 ELSE 0 END)";
				$groupByValo = "group by categorie_id, a.produit_id, a.id, a.date, cpt.prix_unitaire_hors_promo";
				break;
			default:
				$selectQuery = "a.ca_complet";
				$joinQuery = "";
				$selectValoQuery = "1";
				$groupByValo = "";
				break;
		}

  		if ($byYear)
  		{
  			// Complete year
			$query = "select categorie_id, categorie, laboratoire, annee, especes, 
					  manque_periode, manque_periode_prec, ca_periode, ca_periode_prec
					from
					(
						select distinct c.id AS categorie_id, c.nom AS categorie, (CASE WHEN c.laboratoire_id IS NOT NULL THEN l.nom ELSE 'Multi-laboratoires' END) AS laboratoire, c.annee, liste_especes.especes, 
						(case when calcul_periode.nb_produits != calcul_periode.nb_valos then true else false end) as manque_periode, 
						(case when calcul_periode.nb_produits_prec != calcul_periode.nb_valos_prec then true else false end) as manque_periode_prec, 
						coalesce(calcul_periode.ca_periode, 0) as ca_periode, 
						coalesce(calcul_periode.ca_periode_prec, 0) AS ca_periode_prec
						from categories c
						left join laboratoires l on l.id = c.laboratoire_id
						left join
						(	
							select categorie_espece.categorie_id, string_agg(especes.nom, ', ' order by especes.nom) AS especes
							from especes
							join categorie_espece ON especes.id = categorie_espece.espece_id
							group by categorie_espece.categorie_id
						) liste_especes on liste_especes.categorie_id = c.id
						left join 
						(
							select categorie_id, 
							sum(nb_produits) as nb_produits, sum(nb_produits_prec) as nb_produits_prec, 
							sum(nb_valos) as nb_valos, sum(nb_valos_prec) as nb_valos_prec, 
							round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select" . ($groupByValo != "" ? "" : " distinct") . " cap.categorie_id, a.produit_id, a.id, a.date, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then 1 else 0 end) as nb_produits,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then 1 else 0 end) as nb_produits_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as nb_valos,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as nb_valos_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec
								from achats a
								join produits p on p.id = a.produit_id
								join categorie_produit cap on cap.produit_id = p.id 
								join centrale_clinique cc on cc.id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
								" . $joinQuery . "
								where a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
								" . $groupByValo . "
							) achats
							group by categorie_id
						) calcul_periode on calcul_periode.categorie_id = c.id
						where c.obsolete is false 
						" . ($products != null ? "and c.laboratoire_id in (select distinct laboratoire_id from produits where id in (" . implode(",", $products) . "))" : "") . "
						and c.annee in (select distinct extract(year from s.annee)::integer from generate_series('" . $startDate . "'::timestamp, '" . $endDate . "', '1 month') as s(annee))
					) t
					order by annee desc, laboratoire, categorie";
						
  		} else 
  		{
  			// Recherche par mois
	  		$querySelect = "select categorie_id, categorie, laboratoire, annee, especes ";
	  		$queryCrosstabAs = "
				) AS t (categorie_id text, categorie text, laboratoire text, annee text, especes text";
	  		
  			for($i=0 ; $i<$nbMonthDiff ; $i++)
  			{
  				$querySelect .= "
  				, coalesce((m" . $i . ").ca_periode,0) AS ca_periode_M" . $i . ", coalesce((m" . $i . ").ca_periode_prec,0) AS ca_periode_prec_M" . $i . ", (m" . $i . ").manque_periode AS manque_periode_M" . $i . ", (m" . $i . ").manque_periode_prec AS manque_periode_prec_M" . $i;

  				$queryCrosstabAs .= "
  				, m" . $i . " achats_sum";
  			}
  			$queryCrosstabAs .= ")";

	  		$queryFrom = "
		  		FROM crosstab(
					\$\$select categorie_id, categorie, laboratoire, annee, especes, mois, achats
					from (
						select distinct categories_mois.categorie_id, categories_mois.categorie, categories_mois.laboratoire, categories_mois.annee, categories_mois.especes, categories_mois.mois AS mois
						, (calcul_periode.ca_periode, calcul_periode.ca_periode_prec, 0, 0, (case when calcul_periode.nb_produits != calcul_periode.nb_valos then true else false end), (case when calcul_periode.nb_produits_prec != calcul_periode.nb_valos_prec then true else false end))::achats_sum as achats
						FROM 
						(
							SELECT distinct c.id AS categorie_id, c.nom AS categorie, (CASE WHEN c.laboratoire_id IS NOT NULL THEN l.nom ELSE 'Multi-laboratoires' END) AS laboratoire, c.annee, liste_especes.especes, liste_mois.g_date::date AS mois 
					        FROM categories c
							left join laboratoires l on l.id = c.laboratoire_id
							left join
							(	
								select categorie_espece.categorie_id, string_agg(especes.nom, ', ' order by especes.nom) AS especes
								from especes
								join categorie_espece ON especes.id = categorie_espece.espece_id
								group by categorie_espece.categorie_id
							) liste_especes on liste_especes.categorie_id = c.id,
					    	(
								select g_date, (case when extract(month from g_date) < 10 then '0' else '' end || extract(month from g_date) || '-' || extract(year from g_date)) as mois 
								from generate_series('" . $startDate . "'::timestamp, '" . $endDate . "'::timestamp, '1 month') as g(g_date)
							) liste_mois
							where c.obsolete is false 
							" . ($products != null ? "and c.laboratoire_id in (select distinct laboratoire_id from produits where id in (" . implode(",", $products) . "))" : "") . "
							and c.annee in (select distinct extract(year from s.annee)::integer from generate_series('" . $startDate . "'::timestamp, '" . $endDate . "', '1 month') as s(annee))
						) categories_mois
					    LEFT JOIN (
							select categorie_id, mois, 
							sum(nb_produits) as nb_produits, sum(nb_produits_prec) as nb_produits_prec, 
							sum(nb_valos) as nb_valos, sum(nb_valos_prec) as nb_valos_prec, 
							round(sum(ca_periode),2) as ca_periode, round(sum(ca_periode_prec),2) as ca_periode_prec
							from
							(
								select" . ($groupByValo != "" ? "" : " distinct") . " cap.categorie_id, a.produit_id, a.id, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then a.date else (a.date + interval '1 year') end) as mois, 
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then 1 else 0 end) as nb_produits,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then 1 else 0 end) as nb_produits_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectValoQuery . " else 0 end) as nb_valos,
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectValoQuery . " else 0 end) as nb_valos_prec,
								(case when (a.date between '" . $startDate . "' and '" . $endDate . "') then " . $selectQuery . " else 0 end) as ca_periode, 
								(case when (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "') then " . $selectQuery . " else 0 end) as ca_periode_prec
								from achats a
								join produits p on p.id = a.produit_id
								join categorie_produit cap on cap.produit_id = p.id 
								join centrale_clinique cc on cc.id = a.centrale_clinique_id 
								" . ($clinics != null ? "" : "join cliniques c on c.id = cc.clinique_id") . "
								" . ($products != null ? "": "join laboratoires l on l.id = p.laboratoire_id") . "
								" . $joinQuery . "
								where a.obsolete IS FALSE and ((a.date between '" . $startDate . "' and '" . $endDate . "') or (a.date between '" . $startDatePrec . "' and '" . $endDatePrec . "'))
								" . ($clinics != null ? "and cc.clinique_id in (" . implode(",", $clinics) . ")" : "and c.obsolete is false and EXTRACT(year from c.date_entree) <= extract(year from current_date)") . "
								" . ($products != null ? "and a.produit_id in (" . implode(",", $products) . ")" : "and p.invisible is false") . "
								" . ($centralPurchasing != null && count($centralPurchasing) > 0 ? "and cc.centrale_id in (".implode(",", $centralPurchasing).")" : "") . "
								" . $groupByValo . "
							) achats
							group by categorie_id, mois
						) calcul_periode on calcul_periode.categorie_id = categories_mois.categorie_id and calcul_periode.mois = categories_mois.mois
					) t\$\$
					, 'select g_date::date as mois from generate_series(''" . $startDate . "''::timestamp, ''" . $endDate . "''::timestamp, ''1 month'') as g(g_date) order by mois'";
			
  			$query = $querySelect . $queryFrom . $queryCrosstabAs;
  		}
	  	
	  	$result = DB::select(DB::raw($query));

        return $result;
	}

	public function findLastDateOfPurchasesByYear($year)
	{
		$query = DB::table('achats')->select(DB::raw("max(date) AS date"));

		if ($year != null)
			$query->where(DB::raw("extract(year from date)"), $year);
		
		$result = $query->first();
		
		return $result->date;
	}

}