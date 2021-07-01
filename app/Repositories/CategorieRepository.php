<?php

namespace App\Repositories;

use App\Model\Categorie;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;

class CategorieRepository implements CategorieRepositoryInterface
{

    protected $categorie;

	public function __construct(Categorie $categorie)
	{
		$this->categorie = $categorie;
	}

	public function findAll($laboratoireId)
	{
		$query = $this->categorie
					->select('categories.nom AS categorie', 'categories.annee AS annee', DB::raw("COALESCE(laboratoires.nom, 'Multi-laboratoires') AS laboratoire"), DB::raw("count(categorie_produit.id) AS nb_produits"), 'categories.id', 'liste_especes.especes', 'liste_especes.especes_noms', 'categories.laboratoire_id', 'categories.country_id', 'ctry_name as country', 'categories.within_agreement', 'categories.show_in_member_reports', 'categories.discount_on_invoice', 'categories.type', 'liste_centrales.centrales', 'liste_centrales.centrales_noms')
					->join('ed_country', 'ctry_id', '=', 'categories.country_id')
        			->leftJoin('laboratoires','laboratoires.id', '=', 'categories.laboratoire_id')
        			->leftJoin('categorie_produit', 'categorie_produit.categorie_id', '=' ,'categories.id')
        			->leftJoin('produits', function($join)
        				{
        					$join->on('produits.id', '=', 'categorie_produit.produit_id');
        					$join->where('produits.invisible', '=', '0');

        				})
					->leftJoin(DB::raw("(select ce.categorie_id, string_agg(ce.espece_id::character, '|') AS especes, string_agg(e.nom, ',' order by e.nom) AS especes_noms
							from categorie_espece ce
							join especes e on e.id = ce.espece_id
						    group by ce.categorie_id
						) liste_especes"),function($join){
						$join->on("liste_especes.categorie_id","=","categories.id");
					})
					->leftJoin(DB::raw("(select cace.categorie_id, string_agg(cace.centrale_id::character varying(10), '|' order by cace.centrale_id) AS centrales, string_agg(ce.nom::character varying(100), ', ' order by ce.nom) AS centrales_noms
							from categorie_centrale cace
							join centrales ce on ce.id = cace.centrale_id
							group by cace.categorie_id
						) liste_centrales"),function($join){
						$join->on("liste_centrales.categorie_id","=","categories.id");
					})
					->where('categories.obsolete', '=', '0')
					->groupBy('categorie', 'laboratoire', 'categories.id', 'liste_especes.especes', 'liste_especes.especes_noms', 'country_id', 'country', 'within_agreement', 'show_in_member_reports', 'discount_on_invoice', 'type', 'centrales', 'centrales_noms');

		if ($laboratoireId != null)
		{
			$query->where('laboratoires.id', '=', $laboratoireId);
		}
		
        return $query->get();
	}

	public function findById($id)
	{
		$query = $this->categorie
        			->select('categories.nom AS categorie', 'categories.annee AS annee', DB::raw("COALESCE(laboratoires.nom, 'Multi-laboratoires') AS laboratoire"), DB::raw("count(categorie_produit.id) AS nb_produits"), 'categories.id', 'liste_especes.especes', 'liste_especes.especes_noms', 'categories.laboratoire_id', 'categories.country_id', 'ctry_name as country', 'categories.within_agreement', 'categories.show_in_member_reports', 'categories.discount_on_invoice', 'categories.type', 'liste_centrales.centrales', 'liste_centrales.centrales_noms')
					->join('ed_country', 'ctry_id', '=', 'categories.country_id')
        			->leftJoin('laboratoires','laboratoires.id', '=', 'categories.laboratoire_id')
        			->leftJoin('categorie_produit', 'categorie_produit.categorie_id', '=' ,'categories.id')
        			->leftJoin('produits', function($join)
        				{
        					$join->on('produits.id', '=', 'categorie_produit.produit_id');
        					$join->where('produits.invisible', '=', '0');

        				})
					->leftJoin(DB::raw("(select ce.categorie_id, string_agg(ce.espece_id::character, '|') AS especes, string_agg(e.nom, ',' order by e.nom) AS especes_noms
							from categorie_espece ce
							join especes e on e.id = ce.espece_id
							group by ce.categorie_id
						) liste_especes"),function($join){
						$join->on("liste_especes.categorie_id","=","categories.id");
					})
					->leftJoin(DB::raw("(select cace.categorie_id, string_agg(cace.centrale_id::character varying(10), '|' order by cace.centrale_id) AS centrales, string_agg(ce.nom::character varying(100), ', ' order by ce.nom) AS centrales_noms
							from categorie_centrale cace
							join centrales ce on ce.id = cace.centrale_id
							group by cace.categorie_id
						) liste_centrales"),function($join){
						$join->on("liste_centrales.categorie_id","=","categories.id");
					})
					->where('categories.obsolete', '=', '0')
					->where('categories.id', '=', $id)
					->groupBy('categorie', 'laboratoire', 'categories.id', 'liste_especes.especes', 'liste_especes.especes_noms', 'country_id', 'country', 'within_agreement', 'show_in_member_reports', 'discount_on_invoice', 'type', 'centrales', 'centrales_noms');

        return $query->get();
	}

	public function findListCommentsByCategorieId($id)
	{
		$query = $this->categorie
					->select('categorie_commentaires.commentaire', 'categorie_commentaires.date', 'users.name')
					->join('categorie_commentaires','categorie_commentaires.categorie_id', '=', 'categories.id')
					->join('users','categorie_commentaires.user_id', '=', 'users.id')
					->where('categories.id', '=', $id)
					->orderBy('categorie_commentaires.date');

        return $query->get();
	}

	/**
	* Recherche la liste des statistiques des objectifs en fonction des paramètres.
	*	@param moisDeb le mois de début de la période
	*	@param anneeDeb l'année de début de la période
	*	@param moisFin le mois de fin de la période
	*	@param anneeFin l'année de fin de la période
	*	@param cliniques la liste des cliniques à prendre en compte
	*	@param laboratoires la liste des laboratoires à prendre en compte
	*	@param types la liste des types à prendre en compte
	*	@param especes la liste des espèces à prendre en compte
	*
	*	@return un tableau contenant la liste des statistiques de la période et celle de la période précédente
	*/
	public function findByParams($moisDeb, $anneeDeb, $moisFin, $anneeFin, $cliniques, $anneesCliniques, $laboratoires, $types, $especes)
	{
		$dateDeb = Carbon::create($anneeDeb, $moisDeb, 1, 0, 0, 0);
		$dateFin = Carbon::create($anneeFin, $moisFin, 1, 0, 0, 0)->endOfMonth();
		$nbAnneeDiff = $dateDeb->diffInYears($dateFin) + 1;
		$dateDebPrec = Carbon::create((int)$anneeDeb-$nbAnneeDiff, $moisDeb, 1, 0, 0, 0);
	  	$dateFinPrec = Carbon::create((int)$anneeFin-$nbAnneeDiff, $moisFin, 1, 0, 0, 0)->endOfMonth();

		$query = "select distinct categories.id AS cat_id, categories.nom AS cat_nom, categories.annee, liste_especes.especes, COALESCE(laboratoires.nom, 'Multi-laboratoires') AS lab_nom,
			coalesce(calcul_periode.ca_periode,0) AS ca_periode, 
			coalesce(calcul_periode_prec.ca_periode,0) AS ca_periode_prec
			FROM categories
			LEFT JOIN laboratoires ON laboratoires.id = categories.laboratoire_id
			LEFT JOIN (
				select categorie_espece.categorie_id, string_agg(categorie_espece.espece_id::character, '|') AS especes
				from categorie_espece
				group by categorie_espece.categorie_id
			) liste_especes on liste_especes.categorie_id = categories.id 
			LEFT JOIN (
				select cat_id, cat_nom,
				round(sum(ca_periode)::numeric,2) AS ca_periode
				FROM (
				    select distinct categories.id AS cat_id, categories.nom AS cat_nom, achats.id, achats.ca_complet AS ca_periode
					from categories
					join categorie_produit on categorie_produit.categorie_id = categories.id
					join produits on produits.id = categorie_produit.produit_id
					left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between '" . $dateDeb . "' and '" . $dateFin . "')
					join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
					join cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.country_id = categories.country_id
					join produit_type on produits.id = produit_type.produit_id 
					join types on types.id = produit_type.type_id and types.id " . ($types != null && count($types) > 0 ? ("in (".implode(",", $types).")") : "is null") . " 
					join espece_produit on produits.id = espece_produit.produit_id 
					join especes on especes.id = espece_produit.espece_id and especes.id " . ($especes != null && count($especes) > 0 ? ("in (".implode(",", $especes).")") : "is null") . "
					where categories.obsolete is false
					and produits.invisible is false
					" . (($laboratoires == null || in_array('0', $laboratoires)) ? "" : ("and categories.laboratoire_id in (".implode(",", $laboratoires).")")) . "
					" . (($cliniques == null || in_array('0', $cliniques)) ? "" : ("and cliniques.id in (".implode(",", $cliniques).")")) . "
					" . (($anneesCliniques == null || in_array('0', $anneesCliniques)) ? "" : ("and EXTRACT(YEAR from cliniques.date_entree) in (".implode(",", $anneesCliniques).")")) . "
				) achats_periode
				group by cat_id, cat_nom) calcul_periode ON calcul_periode.cat_id = categories.id
			LEFT JOIN (
				select cat_id, cat_nom,
				round(sum(ca_periode)::numeric,2) AS ca_periode
				FROM (
				    select distinct categories.id AS cat_id, categories.nom AS cat_nom, achats.id, achats.ca_complet AS ca_periode
					from categories
					join categorie_produit on categorie_produit.categorie_id = categories.id
					join produits on produits.id = categorie_produit.produit_id
					left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between '" . $dateDebPrec . "' and '" . $dateFinPrec . "')
					join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
					join cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.country_id = categories.country_id
					join produit_type on produits.id = produit_type.produit_id 
					join types on types.id = produit_type.type_id and types.id " . ($types != null && count($types) > 0 ? ("in (".implode(",", $types).")") : "is null") . " 
					join espece_produit on produits.id = espece_produit.produit_id 
					join especes on especes.id = espece_produit.espece_id and especes.id " . ($especes != null && count($especes) > 0 ? ("in (".implode(",", $especes).")") : "is null") . "
					where categories.obsolete is false
					and produits.invisible is false
					" . (($laboratoires == null || in_array('0', $laboratoires)) ? "" : ("and categories.laboratoire_id in (".implode(",", $laboratoires).")")) . "
					" . (($cliniques == null || in_array('0', $cliniques)) ? "" : ("and cliniques.id in (".implode(",", $cliniques).")")) . "
					" . (($anneesCliniques == null || in_array('0', $anneesCliniques)) ? "" : ("and EXTRACT(YEAR from cliniques.date_entree) in (".implode(",", $anneesCliniques).")")) . "
				) achats_periode
				group by cat_id, cat_nom) calcul_periode_prec ON calcul_periode_prec.cat_id = categories.id
			where categories.obsolete is false
			" . (($laboratoires == null || in_array('0', $laboratoires)) ? "" : ("and categories.laboratoire_id in (".implode(",", $laboratoires).")")) . "
			order by cat_nom";
  		
	  	$result = DB::select(DB::raw($query));

        return $result;
	}

	public function findByCountryAndYearAndSupplier($countryId, $year, $supplierId)
	{
		$query = $this->categorie
					->select('categories.id', 'categories.nom', 'categories.annee', 'categories.laboratoire_id', DB::raw('upper(left(categories.nom, 1))'))
					->where('categories.obsolete', '=', '0')
					->where('annee', '=', $year)
					->where('laboratoire_id', '=', $supplierId)
					->where('country_id', '=', $countryId)
					->orderBy(DB::raw('upper(left(categories.nom, 1))'));

        return $query->distinct()->get();
	}

}