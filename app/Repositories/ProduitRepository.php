<?php

namespace App\Repositories;

use App\Model\Produit;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;
use Session;

class ProduitRepository implements ProduitRepositoryInterface
{

    protected $produit;

	public function __construct(Produit $produit)
	{
		$this->produit = $produit;
	}

	public function findAll($labId)
	{			
		$query = "
			select p.id, l.nom AS laboratoire, p.denomination, p.conditionnement, pv.valo_euro, p.valo_volume, p.unite_valo_volume, p.obsolete, p.invisible, " . (((sizeof(Auth::user()->roles) >0) && ("Administrateur" == Auth::user()->roles[0]['nom']) && Session::get('user_is_super_admin')) ? "(CASE WHEN p.code_gtin is not null THEN CONCAT(p.code_gtin, CASE WHEN p.code_gtin_autre IS NOT NULL THEN ', ' END, REPLACE(p.code_gtin_autre, '|', ', ')) ELSE REPLACE(p.code_gtin_autre, '|', ', ') END) AS code_gtin" : "p.code_gtin") . ", liste_especes.especes_id, liste_types.types_id, (CASE WHEN p.famille_therapeutique_id IS NOT NULL THEN CONCAT(ft.classe1_code, ft.classe2_code, ft.classe3_code, ' (', p.famille_therapeutique_id, ')') ELSE NULL END) as classe_therapeutique, liste_countries.countries
			from produits p 
			join laboratoires l on l.id = p.laboratoire_id
			left join familles_therapeutiques ft on ft.id = p.famille_therapeutique_id
			left join
			(
				select ep.produit_id, string_agg(ep.espece_id::character varying(10), '|') as especes_id
				from espece_produit ep
				group by ep.produit_id
			) liste_especes ON liste_especes.produit_id = p.id
			left join
			(
				select pt.produit_id, string_agg(pt.type_id::character varying(10), '|') as types_id
				from produit_type pt
				group by pt.produit_id
			) liste_types ON liste_types.produit_id = p.id
			left join
			(
				select prco_product_id, string_agg(ctry_name, ', ' order by ctry_name) as countries
				from ed_product_country prco
				join ed_country ctry on ctry_id = prco_country_id
				group by prco_product_id
			) liste_countries ON liste_countries.prco_product_id = p.id
			left join produit_valorisations pv on pv.produit_id = p.id and ((now() > pv.date_debut and pv.date_fin is null) or (now() between pv.date_debut and pv.date_fin))
			where l.obsolete is false
			" . ( ((sizeof(Auth::user()->roles) >0) && ("Administrateur" == Auth::user()->roles[0]['nom']) && Session::get('user_is_super_admin')) ? "" : "and p.invisible is false" ) . "
			" . ( ($labId != null) ? "and l.id = " . $labId : "" ) . "
			order by laboratoire, denomination, conditionnement";
					
        return DB::select(DB::raw($query));
	}

	public function findDetailById($id)
	{
		$query = "
		select distinct p.id as produit_id, ce.id AS centrale_id, ctry_name as country, ce.nom AS centrale_nom, 
			cp.code_produit, cp.obsolete, cp.date_obsolescence, cp.id as cp_id,
			(case when cpe.denomination_mois is not null then cpe.denomination_mois else cpe.denomination_max end) as denomination, 
			(case when cpe.denomination_mois is not null then cpe.denomination_mois_date else cpe.denomination_max_date end) as date_denomination,
			(case when cpe.prix_unitaire_mois is not null then cpe.prix_unitaire_mois else cpe.prix_unitaire_max end) as prix_unitaire, 
			(case when cpe.prix_unitaire_mois is not null then cpe.prix_unitaire_mois_date else cpe.prix_unitaire_max_date end) as date_prix_unitaire
		from produits p
		join centrale_produit cp ON cp.produit_id = p.id
		join ed_country ctry on ctry_id = cp.country_id
		join centrales ce on ce.id = cp.centrale_id 
		left join centrale_produit_encours cpe on cpe.centrale_produit_id = cp.id 
		where p.id = :id
		and ce.obsolete is false
		order by centrale_nom, code_produit";

		$result = DB::select(DB::raw($query), ["id" => $id]);

        return $result;
	}

	public function findListByCategorieId($categorieId)
	{
		$query = $this->produit
					->select('produits.denomination', 'produits.conditionnement', 'produits.code_gtin', 'produits.id', 'produits.obsolete', DB::raw("string_agg(liste_centrales.centrale, ',' order by liste_centrales.centrale) AS source"))
					->join('categorie_produit','categorie_produit.produit_id', '=', 'produits.id')
					->join('categories','categorie_produit.categorie_id', '=', 'categories.id')
					->join(DB::raw("(select distinct cp.produit_id, cp.country_id, ce.nom as centrale
							from centrale_produit cp 
							join centrales ce on ce.id = cp.centrale_id
							where cp.obsolete is false
						) liste_centrales"),function($join){
						$join->on("liste_centrales.produit_id", "=", "produits.id");
						$join->on("liste_centrales.country_id", "=", "categories.country_id");
					})
					->where('categories.id', '=', $categorieId)
					->where ('produits.invisible', '=', '0')
					->groupBy('produits.denomination', 'produits.conditionnement', 'produits.code_gtin', 'produits.id', 'produits.obsolete');

		return $query->get();
	}

	public function findListByObjectifId($objectifId)
	{
		$query = "select produits.denomination, produits.conditionnement, produits.code_gtin, categorie_produit_objectif.id AS obj_prod_id, produits.obsolete
				FROM categorie_produit_objectif
				JOIN categorie_produit ON categorie_produit.id = categorie_produit_objectif.categorie_produit_id
				JOIN produits ON produits.id = categorie_produit.produit_id
				WHERE categorie_produit_objectif.objectif_id = " . $objectifId . "
				AND produits.invisible IS FALSE";

		return DB::select(DB::raw($query));
	}

	public function findListByObjectifIdAndMoisFin($objectifId, $moisFin)
	{
		$query = "select liste.denomination, liste.conditionnement, coalesce(sum(t.qte_payante_complet), 0) AS volume, liste.pourcentage_remise, liste.pourcentage_remise_source, liste.cat_prod_obj_id, liste.obsolete
				FROM
				(
					SELECT produits.id AS prod_id, produits.denomination, produits.conditionnement, produits.obsolete, cpo.pourcentage_remise, cpo.pourcentage_remise_source, cpo.id AS cat_prod_obj_id
					FROM categorie_produit_objectif cpo
					JOIN categorie_produit cp ON cp.id = cpo.categorie_produit_id
					JOIN produits ON produits.id = cp.produit_id
					WHERE cpo.objectif_id = " . $objectifId . "
				) liste
				LEFT JOIN
				(
					SELECT produits.id AS prod_id, achats.id, achats.qte_payante_complet AS qte_payante_complet
					FROM objectifs
					JOIN categories ON categories.id = objectifs.categorie_id
					JOIN categorie_produit_objectif ON categorie_produit_objectif.objectif_id = objectifs.id
					JOIN categorie_produit ON categorie_produit.id = categorie_produit_objectif.categorie_produit_id
					JOIN produits ON produits.id = categorie_produit.produit_id
					LEFT OUTER JOIN achats ON achats.produit_id = produits.id
					JOIN centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
					JOIN cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.country_id = categories.country_id
					WHERE objectifs.id = " . $objectifId . "
					AND produits.invisible IS FALSE
					AND achats.obsolete IS FALSE
					AND achats.date BETWEEN to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and last_day(to_date('01/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY'))
					AND EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
					AND cliniques.obsolete is false
					and cliniques.premium = objectifs.premium
					and ((objectifs.except_SE1107 is false) or (objectifs.except_SE1107 is true and cliniques.is_SE1107 is false))
					and ((objectifs.except_opt_out is false) or (objectifs.except_opt_out is true and cliniques.is_opt_out is false))
					and ((objectifs.except_NO18552 is false) or (objectifs.except_NO18552 is true and cliniques.is_NO18552 is false))
					and ((objectifs.except_es_vf2_centauro is false) or (objectifs.except_es_vf2_centauro is true and cliniques.is_es_vf2_centauro is false))
					and ((objectifs.except_es_vf2_nuzoa is false) or (objectifs.except_es_vf2_nuzoa is true and cliniques.is_es_vf2_nuzoa is false))
					and ((objectifs.except_es_vf2_distrivet is false) or (objectifs.except_es_vf2_distrivet is true and cliniques.is_es_vf2_distrivet is false))
					and centrale_clinique.centrale_id in (select cace.centrale_id from categorie_centrale cace join objectifs o2 on o2.categorie_id = cace.categorie_id where o2.id = " . $objectifId . ")
				) t ON t.prod_id = liste.prod_id
				GROUP BY denomination, conditionnement, pourcentage_remise, pourcentage_remise_source, cat_prod_obj_id, obsolete";

		return DB::select(DB::raw($query));
	}

	public function findListCandidatsByLaboratoireAndCategorie($countryId, $laboratoireId, $produitsId)
	{
		$query = $this->produit->distinct()
					->select('denomination', 'conditionnement', 'code_gtin', 'id', 'obsolete', DB::raw("string_agg(liste_centrales.centrale, ',' order by liste_centrales.centrale) AS source"))
					->join('ed_product_country', 'prco_product_id', '=', 'produits.id')
					->join(DB::raw("(select distinct cp.produit_id, ce.nom as centrale
							from centrale_produit cp 
							join centrales ce on ce.id = cp.centrale_id
							where cp.obsolete is false
							and cp.country_id = " . $countryId . "
						) liste_centrales"),function($join){
						$join->on("liste_centrales.produit_id", "=", "produits.id");
					})
					->where ('invisible', '=', '0')
					->groupBy('produits.denomination', 'produits.conditionnement', 'produits.code_gtin', 'produits.id', 'produits.obsolete');

		if ($countryId != null)
		{
			$query->where('prco_country_id', '=', $countryId);
		}
			
		if ($laboratoireId != null && !Session::get('user_is_super_admin'))
		{
			$query->where('laboratoire_id', '=', $laboratoireId);
		}

		if (sizeof($produitsId) > 0)
		{
			$query->whereNotIn('id', $produitsId);
		}

		return $query->get();
	}

	public function findEstimationsRFAForExcel($startMonth, $startYear, $endMonth, $endYear, $clinic, $lab, $clinicCodes, $targetYear)
	{
		$startDate = Carbon::create($startYear, $startMonth, 1, 0, 0, 0);
		$endDate = Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth();
		
		$query = $this->produit
					->select('produits.denomination', 'produits.conditionnement', 'categorie_produit_objectif.pourcentage_remise', 'objectifs.id as objectif_id', 'objectifs.nom as objectif', 'objectifs.type_objectif_id', 'objectifs.incrementiel', DB::raw('COALESCE(calcul_periode.ca_periode, 0) as ca_periode'), DB::raw('COALESCE(calcul_periode.qte_periode, 0) as qte_periode'))
					->join('categorie_produit','produits.id', '=', 'categorie_produit.produit_id')
					->join('categorie_produit_objectif','categorie_produit.id', '=', 'categorie_produit_objectif.categorie_produit_id')
					->join('objectifs','categorie_produit_objectif.objectif_id', '=', 'objectifs.id')
					->join('categories','objectifs.categorie_id', '=', 'categories.id')
        			->leftJoin(DB::raw("(
							select prod_id, obj_id, sum(volume) as qte_periode, (sum(ca_periode)::numeric) as ca_periode
							FROM (
								select prod_id, obj_id, volume,
									(CASE type_valorisation_objectif_id 
										WHEN 1 THEN ca_complet 
										WHEN 2 THEN (prix_unitaire_hors_promo * volume)
										WHEN 3 THEN (valo_euro * volume)
										ELSE NULL 
									END) AS ca_periode
								FROM (
									select distinct p.id AS prod_id, o.id as obj_id, o.type_valorisation_objectif_id, o.valorisation_laboratoire, a.id, a.date AS date, a.ca_complet, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume, a.qte_payante_complet AS volume
									from objectifs o
									join categories cat on cat.id = o.categorie_id
									join categorie_produit_objectif cpo on cpo.objectif_id = o.id
									join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
									join produits p on p.id = cpr.produit_id
									left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and (a.date between '" . $startDate . "' and '" . $endDate . "') and (extract(month from a.date) between extract(month from o.date_debut) and extract(month from o.date_fin))
									join centrale_clinique cc on cc.id = a.centrale_clinique_id 
									join cliniques c on c.id = cc.clinique_id and c.obsolete is false and c.country_id = cat.country_id
									left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
									left join centrale_produit cp on cp.id = a.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where p.invisible is false
									and o.obsolete is false
									and o.suivi is true
									and cat.annee = '" . $targetYear . "'
									and cat.laboratoire_id = '" . $lab . "'
									and c.premium = o.premium
									and ((o.except_SE1107 is false) or (o.except_SE1107 is true and c.is_SE1107 is false))
									and ((o.except_opt_out is false) or (o.except_opt_out is true and c.is_opt_out is false))
									and ((o.except_NO18552 is false) or (o.except_NO18552 is true and c.is_NO18552 is false))
									and ((o.except_es_vf2_centauro is false) or (o.except_es_vf2_centauro is true and c.is_es_vf2_centauro is false))
									and ((o.except_es_vf2_nuzoa is false) or (o.except_es_vf2_nuzoa is true and c.is_es_vf2_nuzoa is false))
									and ((o.except_es_vf2_distrivet is false) or (o.except_es_vf2_distrivet is true and c.is_es_vf2_distrivet is false))
									and cc.centrale_id in (select cace.centrale_id from categorie_centrale cace join objectifs o2 on o2.categorie_id = cace.categorie_id where o2.id = o.id)
									" . (($clinic == null) ? "" : ("and c.id = " . $clinic)) . "
									" . (($clinicCodes == null || in_array('0', $clinicCodes)) ? "" : ("and cc.id in (".implode(",", $clinicCodes).")")) . "
								) achats_periode
							) achats
							group by prod_id, obj_id
						) calcul_periode"),function($join){
						$join->on("calcul_periode.prod_id","=","produits.id");
						$join->on("calcul_periode.obj_id","=","objectifs.id");
					})
					->where('produits.invisible', '=', 0)
					->where('objectifs.obsolete', '=', 0)
					->where('objectifs.suivi', '=', 1)
					->where('categories.annee', '=', $targetYear)
					->where('categories.laboratoire_id', '=', $lab)
					->orderBy('denomination')
					->orderBy('conditionnement')
					->orderBy('objectif');

		return $query->get();
	}

	public function findBilanRFAForExcel($laboratoireId, $annee, $objectifsId, $cliniqueId)
	{
		$dateDeb = Carbon::create($annee, 1, 1, 0, 0, 0);
		$dateFin = Carbon::create($annee, 12, 31, 0, 0, 0);
		
		$query = $this->produit
					->select('produits.denomination', 'produits.conditionnement', 'categorie_produit_objectif.pourcentage_remise', 'objectifs.nom as objectif', DB::raw('COALESCE(calcul_periode.ca_periode, 0) as ca_periode'), DB::raw('COALESCE(calcul_periode.qte_periode, 0) as qte_periode'))
					->join('categorie_produit','produits.id', '=', 'categorie_produit.produit_id')
					->join('categorie_produit_objectif','categorie_produit.id', '=', 'categorie_produit_objectif.categorie_produit_id')
					->join('objectifs','categorie_produit_objectif.objectif_id', '=', 'objectifs.id')
        			->leftJoin(DB::raw("(
        					select prod_id, obj_id, sum(volume) as qte_periode, (round(sum(ca_periode)::numeric,2)) as ca_periode
        					FROM (
	        					select prod_id, obj_id, volume,
									(CASE type_valorisation_objectif_id 
										WHEN 1 THEN ca_complet 
										WHEN 2 THEN (prix_unitaire_hors_promo * volume)
										WHEN 3 THEN (valo_euro * volume)
										ELSE NULL 
									END) AS ca_periode
								FROM (
								    select distinct produits.id AS prod_id, objectifs.id as obj_id, objectifs.type_valorisation_objectif_id, objectifs.valorisation_laboratoire, achats.id, achats.date AS date, achats.ca_complet, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume, achats.qte_payante_complet AS volume
									from objectifs
									join categories on categories.id = objectifs.categorie_id
									join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
									join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
									join produits on produits.id = categorie_produit.produit_id
									left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between objectifs.date_debut and objectifs.date_fin)
									join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
									join cliniques on cliniques.id = centrale_clinique.clinique_id and c.obsolete is false and cliniques.country_id = categories.country_id
									left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
									left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where produits.invisible is false
									and objectifs.id in (" . implode(", ", $objectifsId) . ")
									and EXTRACT(YEAR from cliniques.date_entree) < " . ($annee+1) . "
									" . (($cliniqueId == null) ? "" : ("and cliniques.id = " . $cliniqueId)) . "
									and cliniques.premium = objectifs.premium
									and ((objectifs.except_SE1107 is false) or (objectifs.except_SE1107 is true and cliniques.is_SE1107 is false))
									and ((objectifs.except_opt_out is false) or (objectifs.except_opt_out is true and cliniques.is_opt_out is false))
									and ((objectifs.except_NO18552 is false) or (objectifs.except_NO18552 is true and cliniques.is_NO18552 is false))
									and ((objectifs.except_es_vf2_centauro is false) or (objectifs.except_es_vf2_centauro is true and cliniques.is_es_vf2_centauro is false))
									and ((objectifs.except_es_vf2_nuzoa is false) or (objectifs.except_es_vf2_nuzoa is true and cliniques.is_es_vf2_nuzoa is false))
									and ((objectifs.except_es_vf2_distrivet is false) or (objectifs.except_es_vf2_distrivet is true and cliniques.is_es_vf2_distrivet is false))
									and centrale_clinique.centrale_id in (select cace.centrale_id from categorie_centrale cace join objectifs o2 on o2.categorie_id = cace.categorie_id where o2.id = objectifs.id)
								) achats_periode
							) achats
							group by prod_id, obj_id
						) calcul_periode"),function($join){
						$join->on("calcul_periode.prod_id","=","produits.id");
						$join->on("calcul_periode.obj_id","=","objectifs.id");
					})
					->whereIn('objectifs.id', $objectifsId)
					->orderBy('denomination')
					->orderBy('conditionnement')
					->orderBy('objectif');

		return $query->get();
	}

	public function findExtractionPrixNets($annee, $remiseCentrale)
	{
		$dateDeb = Carbon::create($annee, 1, 1, 0, 0, 0);
		$dateFin = Carbon::create($annee, 12, 31, 0, 0, 0);
		$dateDebPrec = Carbon::create(($annee-1), 1, 1, 0, 0, 0);
		$dateFinPrec = Carbon::create(($annee-1), 12, 31, 0, 0, 0);
		$centId = 1;
		
		$query = "select distinct p.id AS prod_id, p.denomination, p.conditionnement, p.code_gtin, laboratoires.id AS lab_id, laboratoires.nom AS lab_nom, cp.prix_unitaire_hors_promo AS tarif_centrale, produit_valorisations.valo_euro AS tarif_laboratoire, liste_objectifs.cumul_remises, coalesce(calcul_periode.qte_periode,0) AS qte_periode, coalesce(calcul_periode.ca_periode,0) AS ca_periode, coalesce(calcul_periode_prec.qte_periode,0) AS qte_periode_prec, coalesce(calcul_periode_prec.ca_periode,0) AS ca_periode_prec, familles_therapeutiques.classe1_code, familles_therapeutiques.classe1_nom, familles_therapeutiques.classe2_code, familles_therapeutiques.classe2_nom, familles_therapeutiques.classe3_code, familles_therapeutiques.classe3_nom, 
				(((case cp.prix_unitaire_hors_promo when 'N.C.' then null else cp.prix_unitaire_hors_promo::numeric end) * (1 - (" . $remiseCentrale . "::numeric / 100))) - (produit_valorisations.valo_euro * liste_objectifs.cumul_remises::numeric / 100)) AS prix_net
				FROM produits p
				JOIN laboratoires ON laboratoires.id = p.laboratoire_id
				JOIN produit_type on p.id = produit_type.produit_id
				LEFT JOIN familles_therapeutiques on familles_therapeutiques.id = p.famille_therapeutique_id
				LEFT JOIN (
					select distinct centrale_produit_id, produit_id, code_produit, max(prix_unitaire_hors_promo) as prix_unitaire_hors_promo
					from 
					(
						select distinct cp.id as centrale_produit_id, cp.produit_id, cp.code_produit, 
							(CASE 
								WHEN cpt.prix_unitaire_hors_promo IS NULL THEN 'N.C.'
								ELSE cpt.prix_unitaire_hors_promo::numeric::character varying(255)
							END)
						from centrale_produit cp
						left join 
						(
							SELECT centrale_produit_id, MAX(date_creation) as date_max 
							FROM centrale_produit_tarifs
							where qte_tarif::numeric = 1
							GROUP BY centrale_produit_id
						) last_date on last_date.centrale_produit_id = cp.id
						left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and cpt.date_creation = last_date.date_max
						where cp.centrale_id = " . $centId . "
						and cp.produit_id is not null and cp.obsolete is false
					) t 
					group by produit_id, centrale_produit_id, code_produit
					order by produit_id
				) cp on cp.produit_id = p.id
				LEFT JOIN produit_valorisations on produit_valorisations.produit_id = p.id and (('" . $dateFin . "' between produit_valorisations.date_debut and produit_valorisations.date_fin) or ('" . $dateFin . "' >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
				LEFT JOIN
				(
					select distinct categorie_produit.produit_id, sum(categorie_produit_objectif.pourcentage_remise) AS cumul_remises
					FROM objectifs 
					JOIN categories on categories.id = objectifs.categorie_id
					JOIN categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
					JOIN categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
					WHERE objectifs.obsolete IS FALSE and objectifs.suivi IS TRUE
					AND categories.annee = " . $annee . "
					group by categorie_produit.produit_id
				) liste_objectifs on liste_objectifs.produit_id = p.id
				LEFT JOIN
				(
					select prod_id, sum(qte_payante_complet) AS qte_periode, sum(ca_complet) AS ca_periode, centrale_produit_id
					FROM (
					    select distinct produits.id AS prod_id, achats.id, achats.qte_payante_complet, achats.ca_complet, achats.centrale_produit_id
						FROM produits
						JOIN produit_type on produits.id = produit_type.produit_id
						JOIN achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between '" . $dateDeb . "' and '" . $dateFin . "')
						JOIN centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
						JOIN cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.obsolete is false
						WHERE produits.invisible is false
						AND EXTRACT(YEAR from cliniques.date_entree) < " . ($annee+1) . "
					) achats_periode
					group by prod_id, centrale_produit_id
				) calcul_periode ON calcul_periode.centrale_produit_id = cp.centrale_produit_id
				LEFT JOIN
				(
					select prod_id, sum(qte_payante_complet) AS qte_periode, sum(ca_complet) AS ca_periode, centrale_produit_id
					FROM (
					    select distinct produits.id AS prod_id, achats.id, achats.qte_payante_complet, achats.ca_complet, achats.centrale_produit_id
						FROM produits
						JOIN produit_type on produits.id = produit_type.produit_id
						JOIN achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between '" . $dateDebPrec . "' and '" . $dateFinPrec . "')
						JOIN centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
						JOIN cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.obsolete is false
						WHERE produits.invisible is false
						AND EXTRACT(YEAR from cliniques.date_entree) < " . ($annee+1) . "
					) achats_periode
					group by prod_id, centrale_produit_id
				) calcul_periode_prec ON calcul_periode_prec.centrale_produit_id = cp.centrale_produit_id
				WHERE p.invisible is false
				ORDER BY lab_nom, denomination, conditionnement";

	  	$result = DB::select(DB::raw($query));

        return $result;
	}

	public function findAllByParams($laboratories, $productTypes, $productSpecies, $therapeuticClasses, $selectedProducts)
	{
		$params = [];
		$laboratoriesQuery = "";
		if ($laboratories != null && sizeof($laboratories) > 0)
		{
			$laboratoriesQuery = "and p.laboratoire_id in (" . implode(', ', $laboratories) . ")";
			$params[] = implode(', ', $laboratories);
		} 
		$productTypesQuery = "";
		if ($productTypes != null && sizeof($productTypes) > 0)
		{
			$productTypesQuery = "join produit_type pt on pt.produit_id = p.id and pt.type_id in (" . implode(', ', $productTypes) . ")";
			$params[] = implode(', ', $productTypes);
		} 
		$productSpeciesQuery = "";
		if ($productSpecies != null && sizeof($productSpecies) > 0)
		{
			$productSpeciesQuery = "join espece_produit ep on ep.produit_id = p.id and ep.espece_id in (" . implode(', ', $productSpecies) . ")";
			$params[] = implode(', ', $productSpecies);
		}
		$therapeuticClassesQuery = "";
		if ($therapeuticClasses != null && sizeof($therapeuticClasses) > 0)
		{
			$therapeuticClassesQuery = "and p.famille_therapeutique_id in (" . implode(', ', $therapeuticClasses) . ")";
			$params[] = implode(', ', $therapeuticClasses);
		} 

		$selectedProductsQuery = "";
		if ($selectedProducts != null && sizeof($selectedProducts) > 0)
		{
			$selectedProductsQuery = "
			
					union 

					select distinct p.id, p.denomination, p.conditionnement, p.code_gtin, l.nom as laboratoire
					from produits p
					join laboratoires l on l.id = p.laboratoire_id
					where p.id in (" . implode(', ', $selectedProducts) . ")";
			$params[] = implode(', ', $selectedProducts);
		} 

		$query = "select distinct id, denomination, conditionnement, code_gtin, laboratoire
				from
				(
					select distinct p.id, p.denomination, p.conditionnement, p.code_gtin, l.nom as laboratoire
					from produits p
					join laboratoires l on l.id = p.laboratoire_id
					" . $productTypesQuery . "
					" . $productSpeciesQuery . "
					where p.invisible is false 
					" . $laboratoriesQuery . "
					" . $therapeuticClassesQuery . "
					" . $selectedProductsQuery . "
				) liste";
				
		$result = DB::select(DB::raw($query));

		return $result;
	}

	public function findCountByParams($laboratories, $productTypes, $productSpecies, $therapeuticClasses)
	{
		$params = [];
		$laboratoriesQuery = "";
		if ($laboratories != null && sizeof($laboratories) > 0)
		{
			$laboratoriesQuery = "and p.laboratoire_id in (" . implode(', ', $laboratories) . ")";
			$params[] = implode(', ', $laboratories);
		}
		$productTypesQuery = "";
		if ($productTypes != null && sizeof($productTypes) > 0)
		{
			$productTypesQuery = "join produit_type pt on pt.produit_id = p.id and pt.type_id in (" . implode(', ', $productTypes) . ")";
			$params[] = implode(', ', $productTypes);
		}
		$productSpeciesQuery = "";
		if ($productSpecies != null && sizeof($productSpecies) > 0)
		{
			$productSpeciesQuery = "join espece_produit ep on ep.produit_id = p.id and ep.espece_id in (" . implode(', ', $productSpecies) . ")";
			$params[] = implode(', ', $productSpecies);
		} 
		$therapeuticClassesQuery = "";
		if ($therapeuticClasses != null && sizeof($therapeuticClasses) > 0)
		{
			$therapeuticClassesQuery = "and p.famille_therapeutique_id in (" . implode(', ', $therapeuticClasses) . ")";
			$params[] = implode(', ', $therapeuticClasses);
		} 

		$query = "select count(distinct p.id)
				from produits p
				join laboratoires l on l.id = p.laboratoire_id
				" . $productTypesQuery . "
				" . $productSpeciesQuery . "
				where p.invisible is false
				" . $laboratoriesQuery . "
				" . $therapeuticClassesQuery;
				
		$result = DB::select(DB::raw($query));

		return $result;
	}
}