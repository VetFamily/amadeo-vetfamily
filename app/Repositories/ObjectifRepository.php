<?php

namespace App\Repositories;

use DB;
use Carbon\Carbon;
use App\Model\Objectif;
use App\Model\EtatsObjectif;
use Illuminate\Support\Facades\Config;
use App\Repositories\EtatsObjectifRepository;

class ObjectifRepository implements ObjectifRepositoryInterface
{
	protected $objectif;
	private  $etatsObjectifRepository;

	public function __construct(Objectif $objectif, EtatsObjectifRepository $etatsObjectifRepository)
	{
		$this->objectif = $objectif;
		$this->etatsObjectifRepository =  $etatsObjectifRepository;
	}

	public function updateObjectifFromRequest($objectif, $request)
	{
		$data = [
			'nom' => $request->nom,
			'valeur' => $request->valeur,
			'type_objectif_id' => $request->typeObjectif,
			'date_debut' => Carbon::create($request->annee, $request->moisDebut, 1, 0, 0, 0),
			'date_fin' => Carbon::create($request->annee, $request->moisFin, getLastDay($request->annee, $request->moisFin), 0, 0, 0),
			'pourcentage_remise' => $request->remise,
			'pourcentage_remise_source' => $request->remiseSource,
			'remise_additionnelle' => $request->remiseAdditionnelle,
			'objectif_conditionne_id' => $request->idObjConditionne,
			'objectif_precedent_id' => $request->idObjPrecedent,
			'incrementiel' => $request->paliersIncrementiels
		];
		return tap(Objectif::findOrFail($objectif->id))->update($data)->fresh();
	}

	public function updateCAStateAndEcart($objectif, $mois_fin_CA)
	{
		$saveObjectif = $this->findCAById($objectif->id, $objectif->type_valorisation_objectif_id, $mois_fin_CA);
		$dateDebut = new Carbon($objectif->date_debut);
		$annee = $dateDebut->year;
		$moisDebut = $dateDebut->month;
		$moisFin = (new Carbon($objectif->date_fin))->month;
		$objectif->valeur_ca = $saveObjectif[0]->ca_periode;
		$ecartsData = $objectif->getEcarts($annee, $moisDebut, $moisFin, getDaysObjectif($objectif->date_debut, $objectif->date_fin));
		$objectif = $this->setEtat($objectif, $ecartsData);
		$data = [
			'valeur_ca' => $saveObjectif[0]->ca_periode,
			'valeur_ca_prec' => $saveObjectif[0]->ca_periode_prec,
			'valeur_ca_total_prec' => $saveObjectif[0]->ca_periode_total_prec,
			'valeur_atteinte' => (($saveObjectif[0]->ca_periode >= $objectif->valeur) ? true : false),
			'ecart' => (isset($ecartsData['ecart']) ? $ecartsData['ecart'] : null),
			'ecart_unite' => (isset($ecartsData['ecart_unite']) ? $ecartsData['ecart_unite'] : null),
			'etat_objectif_id' => (isset($objectif['etat_objectif_id']) ? $objectif['etat_objectif_id'] : null),
			'poids' => $this->calculatePoids($objectif)
		];
		$objectif->update($data);
		return $saveObjectif;
	}

	public function calculatePoids($objectif)
	{
		$poids = ($objectif->valeur * $objectif->pourcentage_remise) / 100;
		if((3 == $objectif->type_valorisation_objectif_id) && ('Valorisation en volume' == $objectif->valorisation_laboratoire)) {
			$query = "select round(avg(valo_euro / valo_volume), 2) as valo_moyenne
			from
			(
			select p.id, pv.valo_euro, p.valo_volume
			from objectifs o
			join categorie_produit_objectif cpo on cpo.objectif_id = o.id
			join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
			join produits p on p.id = cpr.produit_id
			join produit_valorisations pv on pv.produit_id = p.id and (((date_trunc('month', o.date_fin) + interval '1 month' - interval '1 day')::date between pv.date_debut and pv.date_fin) or ((date_trunc('month', o.date_fin) + interval '1 month' - interval '1 day')::date >= pv.date_debut and pv.date_fin is null))
			where o.id = :id
			) t";
			$poids *= DB::select(DB::raw($query), ['id' => $objectif->id])[0]->valo_moyenne;
		}
		return $poids;
	}

	public function setEtat($objectif, $ecartsData)
	{
		$isValeurAtteinte = $objectif->valeur_atteinte;
		if (isset($isValeurAtteinte) && $isValeurAtteinte) {
			$atteintState = Config::get('constants.etats_objectif.atteint');
			$atteintConditionKO = Config::get('constants.etats_objectif.atteint_condition_ko');
			if (isset($objectif->objectif_conditionne_id)) {
				$objectifConditionne = Objectif::find($objectif->objectif_conditionne_id);
				$objectif->etat_objectif_id = EtatsObjectif::where(
					'nom',
					isset($objectifConditionne->valeur_atteinte) && $objectifConditionne->valeur_atteinte ? $atteintState : $atteintConditionKO
				)->value('id');
			} else {
				$objectif->etat_objectif_id = EtatsObjectif::where('nom',  $atteintState)->value('id');
			}
		} else {
			$ecart = isset($ecartsData['ecart']) ? $ecartsData['ecart'] : null;
			if (isset($ecart)) {
				$objectif->etat_objectif_id = $this->etatsObjectifRepository->findEtatNonAtteintByEcart($ecart)[0]->id;
			}
		}
		return $objectif;
	}

	public function findByLaboratoireIdAndMoisFin($laboratoireId, $moisFin)
	{
		$query = "select distinct o.id as id, o.nom as objectif, o.valeur as valeur, o.suivi, o.type_objectif_id AS type_obj, o.pourcentage_remise, o.pourcentage_remise_source, cat.annee as annee, cat.nom as categorie, l.nom as laboratoire, liste_especes.especes, liste_especes.especes_noms, o.valeur_ca AS ca_periode, o.valeur_ca_prec AS ca_periode_prec, o.manque_valo_periode, o.manque_valo_periode_prec 
			from objectifs o
			join categories cat on cat.id = o.categorie_id 
			left join laboratoires l on l.id = cat.laboratoire_id 
			left join (
				select categorie_espece.categorie_id, string_agg(categorie_espece.espece_id::character, '|') AS especes, string_agg(especes.nom::character varying(100), ', ' order by especes.nom) AS especes_noms
				from categorie_espece
				join especes on especes.id = categorie_espece.espece_id
				group by categorie_espece.categorie_id
			) liste_especes on liste_especes.categorie_id = cat.id 
			where o.obsolete IS FALSE ";

		if ($laboratoireId != null) {
			$query .= "and l.id = " . $laboratoireId;
		}

		return DB::select(DB::raw($query));
	}

	public function findDetailById($id)
	{
		$query = "
		select distinct o.id as id, (CASE WHEN o.date_debut is not null then EXTRACT(MONTH from o.date_debut) else null end) AS mois_debut, (CASE WHEN o.date_fin is not null then EXTRACT(MONTH from o.date_fin) else null end) AS mois_fin, o.pourcentage_remise, o.pourcentage_remise_source, o.remise_additionnelle, o.objectif_conditionne_id AS obj_conditionne, o.objectif_precedent_id AS obj_precedent, o.incrementiel, obj_suivant.id AS obj_suivant, o.valeur_ca_total_prec AS ca_periode_total_prec
			from objectifs o
			left join objectifs obj_suivant on obj_suivant.objectif_precedent_id = o.id
			where o.obsolete IS FALSE 
			and o.id = :id";

		$result = DB::select(DB::raw($query), ["id" => $id]);

        return $result;
	}

	public function findById($id)
	{
		$query = "select distinct objectifs.id as id, objectifs.nom as objectif, objectifs.valeur as valeur, 
			(CASE objectifs.valorisation_laboratoire 
			 	WHEN 'Valorisation en euros' THEN NULL
			 	WHEN 'Valorisation en volume' THEN liste_produits.unite_valo_volume
			 	ELSE NULL
			 END) AS unite_valo_volume, 
			objectifs.suivi, objectifs.sans_engagement, objectifs.type_objectif_id AS type_obj, EXTRACT(MONTH from objectifs.date_debut) AS mois_debut, EXTRACT(MONTH from objectifs.date_fin) AS mois_fin, objectifs.type_valorisation_objectif_id AS type_val_obj, objectifs.pourcentage_decote, objectifs.pourcentage_remise, objectifs.valorisation_laboratoire, objectifs.valorisation_remise, objectifs.remise_additionnelle, objectifs.objectif_conditionne_id AS obj_conditionne, objectifs.objectif_precedent_id AS obj_precedent, objectifs.incrementiel, obj_suivant.id AS obj_suivant, categories.annee as annee, categories.nom as categorie, laboratoires.nom as laboratoire, liste_especes.especes, liste_especes.especes_noms, objectifs.valeur_ca AS ca_periode, objectifs.valeur_ca_prec AS ca_periode_prec, objectifs.valeur_ca_total_prec AS ca_periode_total_prec, objectifs.manque_valo_periode, objectifs.manque_valo_periode_prec 
			from objectifs 
			join categories on categories.id = objectifs.categorie_id 
			left join laboratoires on laboratoires.id = categories.laboratoire_id 
			left join (
				select categorie_espece.categorie_id, string_agg(categorie_espece.espece_id::character, '|') AS especes, string_agg(especes.nom::character varying(100), ', ' order by especes.nom) AS especes_noms
				from categorie_espece
				join especes on especes.id = categorie_espece.espece_id
				group by categorie_espece.categorie_id
			) liste_especes on liste_especes.categorie_id = categories.id 
			left join objectifs obj_suivant on obj_suivant.objectif_precedent_id = objectifs.id
			left join (
				select categorie_produit_objectif.objectif_id, produits.unite_valo_volume
				from categorie_produit_objectif
				join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
				join produits on produits.id = categorie_produit.produit_id
			) liste_produits on liste_produits.objectif_id = objectifs.id
			where objectifs.id = " . $id;

		return DB::select(DB::raw($query));
	}

	public function findListCommentsByObjectifId($id)
	{
		$query = $this->objectif
			->select('objectif_commentaires.commentaire', 'objectif_commentaires.date', 'users.name')
			->join('objectif_commentaires', 'objectif_commentaires.objectif_id', '=', 'objectifs.id')
			->join('users', 'objectif_commentaires.user_id', '=', 'users.id')
			->where('objectifs.id', '=', $id)
			->orderBy('objectif_commentaires.created_at');

		return $query->get();
	}

	public function findCAById($id, $typeValorisation, $moisFin)
	{
		$query = "select id, nom, annee, categorie, laboratoire, especes, especes_noms, ca_periode, ca_periode_prec, ca_periode_total_prec, manque_valo_periode, manque_valo_periode_prec
				from ( ";

		switch ($typeValorisation) {
			case 1:
				$query .= "
					(select objectifs.id as id, objectifs.nom, categories.annee as annee, categories.nom as categorie, laboratoires.nom as laboratoire, liste_especes.especes, liste_especes.especes_noms, calcul_periode.ca_periode AS ca_periode, calcul_periode_prec.ca_periode AS ca_periode_prec, calcul_periode_total_prec.ca_periode AS ca_periode_total_prec, objectifs.manque_valo_periode AS manque_valo_periode, objectifs.manque_valo_periode_prec AS manque_valo_periode_prec 
					from objectifs 
					join categories on categories.id = objectifs.categorie_id 
					left join laboratoires on laboratoires.id = categories.laboratoire_id 
					left join (
						select categorie_espece.categorie_id, string_agg(categorie_espece.espece_id::character varying(10), '|' order by categorie_espece.espece_id) AS especes, string_agg(especes.nom::character varying(100), ', ' order by especes.nom) AS especes_noms
						from categorie_espece
						join especes on especes.id = categorie_espece.espece_id
						group by categorie_espece.categorie_id
					) liste_especes on liste_especes.categorie_id = categories.id 
					left join (
						select obj_id, round(sum(ca_periode)::numeric,2) AS ca_periode
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.ca_complet AS ca_periode
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY')		 
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1) 
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode on calcul_periode.obj_id = objectifs.id 
					left join (
						select obj_id, round(sum(ca_periode)::numeric,2) AS ca_periode
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.ca_complet AS ca_periode
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							where achats.date between ";

				if ($moisFin != null) {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
								 	WHEN EXTRACT(YEAR from current_date) THEN 
								 		CASE 
								 			WHEN EXTRACT(MONTH from objectifs.date_fin) < " . $moisFin . " THEN EXTRACT(MONTH from objectifs.date_fin)
								 			ELSE " . $moisFin . "
								 		END
								 	ELSE EXTRACT(MONTH from objectifs.date_fin)
								 END || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				} else {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				}

				$query .= "					 
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode_prec on calcul_periode_prec.obj_id = objectifs.id 
					left join (
						select obj_id, round(sum(ca_periode)::numeric,2) AS ca_periode
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.ca_complet AS ca_periode
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode_total_prec on calcul_periode_total_prec.obj_id = objectifs.id ";
				break;

			case 2:
				$query .= "
					(select objectifs.id as id, objectifs.nom, categories.annee as annee, categories.nom as categorie, laboratoires.nom as laboratoire, liste_especes.especes, liste_especes.especes_noms, calcul_periode.ca_periode AS ca_periode, calcul_periode_prec.ca_periode AS ca_periode_prec, calcul_periode_total_prec.ca_periode AS ca_periode_total_prec, produits_periode.manque AS manque_valo_periode, produits_periode_prec.manque AS manque_valo_periode_prec  
					from
					(
						select (CASE WHEN count(*) > 0 THEN true ELSE false END) AS manque
						FROM 
						(
							select distinct categorie_produit.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE and achats.qte_payante_complet != 0
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and produits.obsolete is false and produits.invisible is false
						) t
						where t.produit_id not in (
							select distinct achats.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							join centrale_produit on centrale_produit.id = achats.centrale_produit_id
							join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						)
					) produits_periode,
					(
						select (CASE WHEN count(*) > 0 THEN true ELSE false END) AS manque
						FROM 
						(
							select distinct categorie_produit.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE and achats.qte_payante_complet != 0
							where achats.date between ";

				if ($moisFin != null) {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
								 	WHEN EXTRACT(YEAR from current_date) THEN " . $moisFin . "
								 	ELSE EXTRACT(MONTH from objectifs.date_fin)
								 END || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				} else {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				}

				$query .= "					 
							and objectifs.id = " . $id . "
							and produits.obsolete is false and produits.invisible is false
						) t
						where t.produit_id not in (
							select distinct achats.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							join centrale_produit on centrale_produit.id = achats.centrale_produit_id
							join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							where achats.date between ";

				if ($moisFin != null) {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
								 	WHEN EXTRACT(YEAR from current_date) THEN " . $moisFin . "
								 	ELSE EXTRACT(MONTH from objectifs.date_fin)
								 END || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				} else {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				}

				$query .= "	
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						)
					) produits_periode_prec,
					objectifs 
					join categories on categories.id = objectifs.categorie_id 
					left join laboratoires on laboratoires.id = categories.laboratoire_id 
					left join (
						select categorie_espece.categorie_id, string_agg(categorie_espece.espece_id::character varying(10), '|' order by categorie_espece.espece_id) AS especes, string_agg(especes.nom::character varying(100), ', ' order by especes.nom) AS especes_noms
						from categorie_espece
						join especes on especes.id = categorie_espece.espece_id
						group by categorie_espece.categorie_id
					left join (
						select obj_id, round(sum(vol_periode * prix_unitaire_hors_promo)::numeric,2) AS ca_periode
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.qte_payante_complet AS vol_periode, centrale_produit_tarifs.prix_unitaire_hors_promo
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode on calcul_periode.obj_id = objectifs.id 
					left join (
						select obj_id, round(sum(vol_periode * prix_unitaire_hors_promo)::numeric,2) AS ca_periode
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.qte_payante_complet AS vol_periode, centrale_produit_tarifs.prix_unitaire_hors_promo
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							where achats.date between ";

				if ($moisFin != null) {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
								 	WHEN EXTRACT(YEAR from current_date) THEN " . $moisFin . "
								 	ELSE EXTRACT(MONTH from objectifs.date_fin)
								 END || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				} else {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				}

				$query .= "	
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode_prec on calcul_periode_prec.obj_id = objectifs.id 
					left join (
						select obj_id, round(sum(vol_periode * prix_unitaire_hors_promo)::numeric,2) AS ca_periode
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.qte_payante_complet AS vol_periode, centrale_produit_tarifs.prix_unitaire_hors_promo
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode_total_prec on calcul_periode_total_prec.obj_id = objectifs.id ";
				break;

			case 3:
				$query .= "
					(select objectifs.id as id, objectifs.nom, categories.annee as annee, categories.nom as categorie, laboratoires.nom as laboratoire, liste_especes.especes, liste_especes.especes_noms,
						(CASE objectifs.valorisation_laboratoire 
						 	WHEN 'Valorisation en euros' THEN calcul_periode.ca_periode_valo1
						 	WHEN 'Valorisation en volume' THEN calcul_periode.ca_periode_valo2
						 	ELSE NULL
						 END) AS ca_periode,
						 (CASE objectifs.valorisation_laboratoire 
						 	WHEN 'Valorisation en euros' THEN calcul_periode_prec.ca_periode_valo1
						 	WHEN 'Valorisation en volume' THEN calcul_periode_prec.ca_periode_valo2
						 	ELSE NULL
						 END) AS ca_periode_prec, 
						 (CASE objectifs.valorisation_laboratoire 
						 	WHEN 'Valorisation en euros' THEN calcul_periode_total_prec.ca_periode_valo1
						 	WHEN 'Valorisation en volume' THEN calcul_periode_total_prec.ca_periode_valo2
						 	ELSE NULL
						 END) AS ca_periode_total_prec,
						 produits_periode.manque AS manque_valo_periode, produits_periode_prec.manque AS manque_valo_periode_prec 
					from
					(
						select (CASE WHEN count(*) > 0 THEN true ELSE false END) AS manque
						FROM 
						(
							select distinct categorie_produit.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE and achats.qte_payante_complet != 0
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and produits.obsolete is false and produits.invisible is false
						) t
						where t.produit_id not in (
							select distinct achats.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							join produit_valorisations on produit_valorisations.produit_id = categorie_produit.produit_id
							join produits on produits.id = categorie_produit.produit_id
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY')
							and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						)
					) produits_periode,
					(
						select (CASE WHEN count(*) > 0 THEN true ELSE false END) AS manque
						FROM 
						(
							select distinct categorie_produit.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE and achats.qte_payante_complet != 0
							where achats.date between ";

				if ($moisFin != null) {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
								 	WHEN EXTRACT(YEAR from current_date) THEN " . $moisFin . "
								 	ELSE EXTRACT(MONTH from objectifs.date_fin)
								 END || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				} else {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				}

				$query .= "					 
							and objectifs.id = " . $id . "
							and produits.obsolete is false and produits.invisible is false
						) t
						where t.produit_id not in (
							select distinct achats.produit_id
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							join produit_valorisations on produit_valorisations.produit_id = categorie_produit.produit_id
							join produits on produits.id = categorie_produit.produit_id
							where achats.date between ";

				if ($moisFin != null) {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
								 	WHEN EXTRACT(YEAR from current_date) THEN " . $moisFin . "
								 	ELSE EXTRACT(MONTH from objectifs.date_fin)
								 END || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				} else {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				}

				$query .= "					 
							and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						)
					) produits_periode_prec,
					objectifs 
					join categories on categories.id = objectifs.categorie_id 
					left join laboratoires on laboratoires.id = categories.laboratoire_id 
					left join (
						select categorie_espece.categorie_id, string_agg(categorie_espece.espece_id::character varying(10), '|' order by categorie_espece.espece_id) AS especes, string_agg(especes.nom::character varying(100), ', ' order by especes.nom) AS especes_noms
						from categorie_espece
						join especes on especes.id = categorie_espece.espece_id
						group by categorie_espece.categorie_id
					) liste_especes on liste_especes.categorie_id = categories.id 
					left join (
						select obj_id, round(sum(vol * valo_euro)::numeric,2) AS ca_periode_valo1, round(sum(vol * valo_volume)::numeric,2) AS ca_periode_valo2
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.qte_payante_complet AS vol, produit_valorisations.valo_euro, produits.valo_volume
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							left join produit_valorisations on produit_valorisations.produit_id = categorie_produit.produit_id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
							join produits on produits.id = categorie_produit.produit_id
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode on calcul_periode.obj_id = objectifs.id 
					left join (
						select obj_id, round(sum(vol * valo_euro)::numeric,2) AS ca_periode_valo1, round(sum(vol * valo_volume)::numeric,2) AS ca_periode_valo2
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.qte_payante_complet AS vol, produit_valorisations.valo_euro, produits.valo_volume
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							left join produit_valorisations on produit_valorisations.produit_id = categorie_produit.produit_id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
							join produits on produits.id = categorie_produit.produit_id
							where achats.date between ";

				if ($moisFin != null) {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
								 	WHEN EXTRACT(YEAR from current_date) THEN " . $moisFin . "
								 	ELSE EXTRACT(MONTH from objectifs.date_fin)
								 END || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				} else {
					$query .= "to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/'  || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')";
				}

				$query .= "					 
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode_prec on calcul_periode_prec.obj_id = objectifs.id 
					left join (
						select obj_id, round(sum(vol * valo_euro)::numeric,2) AS ca_periode_valo1, round(sum(vol * valo_volume)::numeric,2) AS ca_periode_valo2
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.qte_payante_complet AS vol, produit_valorisations.valo_euro, produits.valo_volume
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join cliniques on cliniques.id = centrale_clinique.clinique_id
							left join produit_valorisations on produit_valorisations.produit_id = categorie_produit.produit_id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
							join produits on produits.id = categorie_produit.produit_id
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')
							and objectifs.id = " . $id . "
							and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
							and cliniques.obsolete IS FALSE
						) achats_periode
						group by obj_id
					) calcul_periode_total_prec on calcul_periode_total_prec.obj_id = objectifs.id ";
				break;

			default:
				$query .= " 
					(select objectifs.id as id, objectifs.nom, categories.annee as annee, categories.nom as categorie, laboratoires.nom as laboratoire, liste_especes.especes, liste_especes.especes_noms, NULL AS ca_periode, NULL AS ca_periode_prec, NULL AS ca_periode_total_prec, objectifs.manque_valo_periode AS manque_valo_periode, objectifs.manque_valo_periode_prec AS manque_valo_periode_prec  
					from objectifs 
					join categories on categories.id = objectifs.categorie_id 
					left join laboratoires on laboratoires.id = categories.laboratoire_id 
					left join (
						select categorie_espece.categorie_id, string_agg(categorie_espece.espece_id::character varying(10), '|' order by categorie_espece.espece_id) AS especes, string_agg(especes.nom::character varying(100), ', ' order by especes.nom) AS especes_noms
						from categorie_espece
						join especes on especes.id = categorie_espece.espece_id
						group by categorie_espece.categorie_id
					) liste_especes on liste_especes.categorie_id = categories.id ";
				break;
		}

		$query .= "
					left join objectifs obj_suivant on obj_suivant.objectif_precedent_id = objectifs.id
					where objectifs.id = " . $id . ")
				) objectifs";

		return DB::select(DB::raw($query));
	}

	public function findAll($annee)
	{
		$result = $this->objectif
			->select('objectifs.*')
			->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
			->where('objectifs.obsolete', '=', '0');

		if ($annee != null) {
			$result->where('categories.annee', '>', ($annee - 1));
		}

		return $result->get();
	}

	public function findEstimationsRFAForExcel($moisDeb, $anneeDeb, $moisFin, $anneeFin, $clinique, $laboratoire, $codesCliniques, $anneeObj)
	{
		$dateDeb = Carbon::create($anneeDeb, $moisDeb, 1, 0, 0, 0);
		$dateFin = Carbon::create($anneeFin, $moisFin, 28, 0, 0, 0);

		$query = $this->objectif
			->select('objectifs.id', 'objectifs.nom', DB::raw('(CASE objectifs.type_valorisation_objectif_id WHEN 1 THEN coalesce(round(sum(calcul_periode.ca_total_achat)::numeric,2), 0) WHEN 2 THEN coalesce(round(sum(calcul_periode.ca_total_centrale)::numeric,2), 0) WHEN 3 THEN coalesce(round(sum(calcul_periode.ca_total_labo)::numeric,2), 0) ELSE NULL END) AS ca_periode'), DB::raw('(CASE objectifs.type_valorisation_objectif_id WHEN 1 THEN (round(sum(calcul_periode.ca_total_achat * categorie_produit_objectif.pourcentage_remise / 100)::numeric,2)) WHEN 2 THEN (round(sum(calcul_periode.ca_total_centrale * categorie_produit_objectif.pourcentage_remise / 100)::numeric,2)) WHEN 3 THEN (round(sum(calcul_periode.ca_total_labo * categorie_produit_objectif.pourcentage_remise / 100)::numeric,2)) ELSE NULL END) AS remise_periode'))
			->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
			->join('categorie_produit_objectif', 'categorie_produit_objectif.objectif_id', '=', 'objectifs.id')
			->join('categorie_produit', 'categorie_produit.id', '=', 'categorie_produit_objectif.categorie_produit_id')
			->leftJoin(DB::raw("(
							select prod_id, sum(ca_total_achat) AS ca_total_achat, sum(ca_total_labo) AS ca_total_labo, sum(ca_total_centrale) AS ca_total_centrale
							from (
							    select distinct produits.id AS prod_id, achats.id, achats.date AS date, achats.ca_complet AS ca_total_achat, (produit_valorisations.valo_euro * achats.qte_payante_complet) AS ca_total_labo, (cpt.prix_unitaire_hors_promo * achats.qte_payante_complet) AS ca_total_centrale, achats.qte_payante_complet AS qte_periode
								from produits
								left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between '" . $dateDeb . "' and '" . $dateFin . "')
								join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
								join cliniques on cliniques.id = centrale_clinique.clinique_id
								left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
								left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
								left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
								where produits.invisible is false
								and cliniques.obsolete IS FALSE
								" . (($clinique == null) ? "" : ("and cliniques.id = " . $clinique)) . "
								" . (($codesCliniques == null || in_array('0', $codesCliniques)) ? "" : ("and centrale_clinique.id in (" . implode(",", $codesCliniques) . ")")) . "
							) achats_periode
							group by prod_id
						) calcul_periode"), function ($join) {
				$join->on("calcul_periode.prod_id", "=", "categorie_produit.produit_id");
			})
			->where('categories.laboratoire_id', '=', $laboratoire)
			->where('categories.annee', '=', $anneeObj)
			->where('objectifs.obsolete', '=', '0')
			->where('objectifs.suivi', '=', '1')
			->groupBy('objectifs.id', 'objectifs.nom')
			->orderBy('nom');

		return $query->get();
	}

	public function findObjectifsAtteints($annee, $moisFin)
	{
		$query = $this->objectif
			->select(
				DB::raw('laboratoires.id as lab_id'),
				DB::raw('laboratoires.nom as lab_nom'),
				DB::raw('categories.nom as cat_nom'),
				'objectifs.id',
				'objectifs.nom',
				'objectifs.valeur',
				'objectifs.pourcentage_remise',
				'calcul_periode.valeur_ca',
				'calcul_periode.valeur_remise',
				'objectifs.type_objectif_id',
				'objectifs.objectif_conditionne_id',
				'objectifs.objectif_precedent_id',
				'objectifs.incrementiel',
				'liste_especes.especes',
				'categories.annee',
				DB::raw('EXTRACT(MONTH from objectifs.date_debut) AS mois_debut'),
				DB::raw('EXTRACT(MONTH from objectifs.date_fin) AS mois_fin'),
				DB::raw("(CASE objectifs.valorisation_laboratoire 
						 	WHEN 'Valorisation en euros' THEN '€'
						 	WHEN 'Valorisation en volume' THEN liste_produits.unite_valo_volume
						 	ELSE '€'
						 END) AS unite")
			)
			->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
			->join('laboratoires', 'laboratoires.id', '=', 'categories.laboratoire_id')
			->leftJoin(DB::raw("(select categorie_espece.categorie_id, string_agg(especes.nom::character varying (100), ', ') AS especes
							from categorie_espece
							join especes on especes.id = categorie_espece.espece_id
							group by categorie_espece.categorie_id
						) liste_especes"), function ($join) {
				$join->on("liste_especes.categorie_id", "=", "categories.id");
			})
			->leftJoin(DB::raw("(select categorie_produit_objectif.objectif_id, produits.unite_valo_volume
							from categorie_produit_objectif
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
						) liste_produits"), function ($join) {
				$join->on("liste_produits.objectif_id", "=", "objectifs.id");
			})
			->leftJoin(DB::raw("(
							select obj_id, (round(sum(ca_periode)::numeric,2)) as valeur_ca, (round(sum(ca_remise * pourcentage_remise / 100)::numeric,2)) AS valeur_remise
							from
							(
								select obj_id, pourcentage_remise,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN
										CASE valorisation_laboratoire 
										 	WHEN 'Valorisation en euros' THEN (valo_euro * volume)
										 	WHEN 'Valorisation en volume' THEN (valo_volume * volume)
										 	ELSE (valo_euro * volume)
										 END 
									ELSE NULL 
								END) AS ca_periode,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN (valo_euro * volume)
									ELSE NULL 
								END) AS ca_remise
								from (
								    select distinct objectifs.id AS obj_id, achats.id, achats.date AS date, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume, categorie_produit_objectif.pourcentage_remise, objectifs.type_valorisation_objectif_id, objectifs.valorisation_laboratoire
									from objectifs
									join categories on categories.id = objectifs.categorie_id 
									join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id 
									join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id 
									join produits on produits.id = categorie_produit.produit_id
									left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
									 	WHEN EXTRACT(YEAR from current_date) THEN 
									 		CASE 
									 			WHEN EXTRACT(MONTH from objectifs.date_fin) < " . $moisFin . " THEN EXTRACT(MONTH from objectifs.date_fin)
									 			ELSE " . $moisFin . "
									 		END
									 	ELSE EXTRACT(MONTH from objectifs.date_fin)
									 END || '/' || categories.annee, 'DD/MM/YYYY'))
									join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
									join cliniques on cliniques.id = centrale_clinique.clinique_id
									left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
									left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where objectifs.obsolete is false
									and objectifs.suivi is true
									and categories.annee = " . $annee . "
									and objectifs.valeur_atteinte is true
									and objectifs.type_valorisation_objectif_id is not null
									and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
									and cliniques.obsolete IS FALSE
								) achats_periode
							) achats
							group by obj_id
						) calcul_periode"), function ($join) {
				$join->on("calcul_periode.obj_id", "=", "objectifs.id");
			})
			->where('objectifs.obsolete', '=', '0')
			->where('objectifs.suivi', '=', '1')
			->where('categories.annee', '=', $annee)
			->where('objectifs.valeur_atteinte', '=', '1')
			->whereNotNull('objectifs.type_valorisation_objectif_id')
			->whereNotNull('objectifs.type_objectif_id');

		return $query->distinct()->get();
	}

	public function findObjectifsNonAtteints($annee, $moisFin)
	{
		$query = $this->objectif
			->select(
				DB::raw('laboratoires.id as lab_id'),
				DB::raw('laboratoires.nom as lab_nom'),
				DB::raw('categories.nom as cat_nom'),
				'objectifs.id',
				'objectifs.nom',
				'objectifs.valeur',
				'objectifs.pourcentage_remise',
				'calcul_periode.valeur_ca',
				'calcul_periode.valeur_remise',
				'objectifs.type_objectif_id',
				'objectifs.objectif_conditionne_id',
				'objectifs.objectif_precedent_id',
				'objectifs.incrementiel',
				'liste_especes.especes',
				'categories.annee',
				DB::raw('EXTRACT(MONTH from objectifs.date_debut) AS mois_debut'),
				DB::raw('EXTRACT(MONTH from objectifs.date_fin) AS mois_fin'),
				DB::raw("(CASE objectifs.valorisation_laboratoire 
						 	WHEN 'Valorisation en euros' THEN '€'
						 	WHEN 'Valorisation en volume' THEN liste_produits.unite_valo_volume
						 	ELSE '€'
						 END) AS unite")
			)
			->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
			->join('laboratoires', 'laboratoires.id', '=', 'categories.laboratoire_id')
			->leftJoin(DB::raw("(select categorie_espece.categorie_id, string_agg(especes.nom::character varying (100), ', ') AS especes
							from categorie_espece
							join especes on especes.id = categorie_espece.espece_id
							group by categorie_espece.categorie_id
						) liste_especes"), function ($join) {
				$join->on("liste_especes.categorie_id", "=", "categories.id");
			})
			->leftJoin(DB::raw("(select categorie_produit_objectif.objectif_id, produits.unite_valo_volume
							from categorie_produit_objectif
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
						) liste_produits"), function ($join) {
				$join->on("liste_produits.objectif_id", "=", "objectifs.id");
			})
			->leftJoin(DB::raw("(
							select obj_id, (round(sum(ca_periode)::numeric,2)) as valeur_ca, (round(sum(ca_remise * pourcentage_remise / 100)::numeric,2)) AS valeur_remise
							from
							(
								select obj_id, pourcentage_remise,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN
										CASE valorisation_laboratoire 
										 	WHEN 'Valorisation en euros' THEN (valo_euro * volume)
										 	WHEN 'Valorisation en volume' THEN (valo_volume * volume)
										 	ELSE (valo_euro * volume)
										 END 
									ELSE NULL 
								END) AS ca_periode,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN (valo_euro * volume)
									ELSE NULL 
								END) AS ca_remise
								from (
								    select distinct objectifs.id AS obj_id, achats.id, achats.date AS date, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume, categorie_produit_objectif.pourcentage_remise, objectifs.type_valorisation_objectif_id, objectifs.valorisation_laboratoire
									from objectifs
									join categories on categories.id = objectifs.categorie_id 
									join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id 
									join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id 
									join produits on produits.id = categorie_produit.produit_id
									left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
									 	WHEN EXTRACT(YEAR from current_date) THEN 
									 		CASE 
									 			WHEN EXTRACT(MONTH from objectifs.date_fin) < " . $moisFin . " THEN EXTRACT(MONTH from objectifs.date_fin)
									 			ELSE " . $moisFin . "
									 		END
									 	ELSE EXTRACT(MONTH from objectifs.date_fin)
									 END || '/' || categories.annee, 'DD/MM/YYYY'))
									join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
									join cliniques on cliniques.id = centrale_clinique.clinique_id
									left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
									left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where objectifs.obsolete is false 
									and objectifs.suivi is true
									and categories.annee = " . $annee . "
									and objectifs.valeur_atteinte is false
									and objectifs.type_valorisation_objectif_id is not null
									and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
									and cliniques.obsolete IS FALSE
								) achats_periode
							) achats
							group by obj_id
						) calcul_periode"), function ($join) {
				$join->on("calcul_periode.obj_id", "=", "objectifs.id");
			})
			->where('objectifs.obsolete', '=', '0')
			->where('objectifs.suivi', '=', '1')
			->where('objectifs.valeur_atteinte', '=', '0')
			->where('categories.annee', '=', $annee)
			->whereNotNull('objectifs.type_valorisation_objectif_id')
			->whereNotNull('objectifs.type_objectif_id');

		return $query->distinct()->get();
	}

	public function findObjectifPrecedent($objectifId, $annee, $moisFin)
	{
		$query = $this->objectif
			->select(
				DB::raw('laboratoires.id as lab_id'),
				DB::raw('laboratoires.nom as lab_nom'),
				DB::raw('categories.nom as cat_nom'),
				'objectifs.id',
				'objectifs.nom',
				'objectifs.valeur',
				'objectifs.pourcentage_remise',
				'calcul_periode.valeur_ca',
				'calcul_periode.valeur_remise',
				'objectifs.valeur_atteinte',
				'objectifs.type_objectif_id',
				'objectifs.objectif_conditionne_id',
				'objectifs.objectif_precedent_id',
				'objectifs.incrementiel',
				'liste_especes.especes',
				'categories.annee',
				DB::raw('EXTRACT(MONTH from objectifs.date_debut) AS mois_debut'),
				DB::raw('EXTRACT(MONTH from objectifs.date_fin) AS mois_fin'),
				DB::raw("(CASE objectifs.valorisation_laboratoire 
						 	WHEN 'Valorisation en euros' THEN '€'
						 	WHEN 'Valorisation en volume' THEN liste_produits.unite_valo_volume
						 	ELSE '€'
						 END) AS unite")
			)
			->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
			->join('laboratoires', 'laboratoires.id', '=', 'categories.laboratoire_id')
			->leftJoin(DB::raw("(select categorie_espece.categorie_id, string_agg(especes.nom::character varying (100), ', ') AS especes
							from categorie_espece
							join especes on especes.id = categorie_espece.espece_id
							group by categorie_espece.categorie_id
						) liste_especes"), function ($join) {
				$join->on("liste_especes.categorie_id", "=", "categories.id");
			})
			->leftJoin(DB::raw("(select categorie_produit_objectif.objectif_id, produits.unite_valo_volume
							from categorie_produit_objectif
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
						) liste_produits"), function ($join) {
				$join->on("liste_produits.objectif_id", "=", "objectifs.id");
			})
			->leftJoin(DB::raw("(
							select obj_id, (round(sum(ca_periode)::numeric,2)) as valeur_ca, (round(sum(ca_remise * pourcentage_remise / 100)::numeric,2)) AS valeur_remise
							from
							(
								select obj_id, pourcentage_remise,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN
										CASE valorisation_laboratoire 
										 	WHEN 'Valorisation en euros' THEN (valo_euro * volume)
										 	WHEN 'Valorisation en volume' THEN (valo_volume * volume)
										 	ELSE (valo_euro * volume)
										 END 
									ELSE NULL 
								END) AS ca_periode,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN (valo_euro * volume)
									ELSE NULL 
								END) AS ca_remise
								from (
								    select distinct objectifs.id AS obj_id, achats.id, achats.date AS date, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume, categorie_produit_objectif.pourcentage_remise, objectifs.type_valorisation_objectif_id, objectifs.valorisation_laboratoire
									from objectifs
									join categories on categories.id = objectifs.categorie_id 
									join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id 
									join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id 
									join produits on produits.id = categorie_produit.produit_id
									left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
									 	WHEN EXTRACT(YEAR from current_date) THEN 
									 		CASE 
									 			WHEN EXTRACT(MONTH from objectifs.date_fin) < " . $moisFin . " THEN EXTRACT(MONTH from objectifs.date_fin)
									 			ELSE " . $moisFin . "
									 		END
									 	ELSE EXTRACT(MONTH from objectifs.date_fin)
									 END || '/' || categories.annee, 'DD/MM/YYYY'))
									join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
									join cliniques on cliniques.id = centrale_clinique.clinique_id
									left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
									left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where objectifs.obsolete is false 
									and objectifs.id = " . $objectifId . "
									and EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
									and cliniques.obsolete IS FALSE
								) achats_periode
							) achats
							group by obj_id
						) calcul_periode"), function ($join) {
				$join->on("calcul_periode.obj_id", "=", "objectifs.id");
			})
			->where('objectifs.id', '=', $objectifId);

		return $query->distinct()->first();
	}

	public function findCACliniqueById($objectifId, $cliniqueId, $moisFin)
	{
		$query = $this->objectif
			->select('objectifs.id', 'objectifs.nom', DB::raw('COALESCE(calcul_periode.valeur_ca, 0) as valeur_ca'), DB::raw('COALESCE(calcul_periode.valeur_remise, 0) as valeur_remise'))
			->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
			->join('laboratoires', 'laboratoires.id', '=', 'categories.laboratoire_id')
			->leftJoin(DB::raw("(
							select obj_id, (round(sum(ca_periode)::numeric,2)) as valeur_ca, (round(sum(ca_remise * pourcentage_remise / 100)::numeric,2)) AS valeur_remise
							from
							(
								select obj_id, pourcentage_remise,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN
										CASE valorisation_laboratoire 
										 	WHEN 'Valorisation en euros' THEN (valo_euro * volume)
										 	WHEN 'Valorisation en volume' THEN (valo_volume * volume)
										 	ELSE (valo_euro * volume)
										 END 
									ELSE NULL 
								END) AS ca_periode,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN (valo_euro * volume)
									ELSE NULL 
								END) AS ca_remise
								from (
								    select distinct objectifs.id AS obj_id, achats.id, achats.date AS date, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume, categorie_produit_objectif.pourcentage_remise, objectifs.type_valorisation_objectif_id, objectifs.valorisation_laboratoire
									from objectifs
									join categories on categories.id = objectifs.categorie_id 
									join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id 
									join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id 
									join produits on produits.id = categorie_produit.produit_id
									left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/' || CASE categories.annee 
									 	WHEN EXTRACT(YEAR from current_date) THEN 
									 		CASE 
									 			WHEN EXTRACT(MONTH from objectifs.date_fin) < " . $moisFin . " THEN EXTRACT(MONTH from objectifs.date_fin)
									 			ELSE " . $moisFin . "
									 		END
									 	ELSE EXTRACT(MONTH from objectifs.date_fin)
									 END || '/' || categories.annee, 'DD/MM/YYYY'))
									join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
									join cliniques on cliniques.id = centrale_clinique.clinique_id
									left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
									left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where objectifs.obsolete is false 
									and objectifs.id = " . $objectifId . "
									and cliniques.id = " . $cliniqueId . "
									and cliniques.obsolete IS FALSE
								) achats_periode
							) achats
							group by obj_id
						) calcul_periode"), function ($join) {
				$join->on("calcul_periode.obj_id", "=", "objectifs.id");
			})
			->where('objectifs.id', '=', $objectifId);

		return $query->first();
	}

	public function findCATotalPrecCliniqueById($objectifId, $cliniqueId)
	{
		$query = $this->objectif
			->select('objectifs.id', DB::raw('COALESCE(calcul_periode.valeur_ca, 0) as valeur_ca'))
			->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
			->join('laboratoires', 'laboratoires.id', '=', 'categories.laboratoire_id')
			->leftJoin(DB::raw("(
							select obj_id, (round(sum(ca_periode)::numeric,2)) as valeur_ca
							from
							(
								select obj_id,
								(CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN
										CASE valorisation_laboratoire 
										 	WHEN 'Valorisation en euros' THEN (valo_euro * volume)
										 	WHEN 'Valorisation en volume' THEN (valo_volume * volume)
										 	ELSE (valo_euro * volume)
										 END 
									ELSE NULL 
								END) AS ca_periode
								from (
								    select distinct objectifs.id AS obj_id, achats.id, achats.date AS date, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume, categorie_produit_objectif.pourcentage_remise, objectifs.type_valorisation_objectif_id, objectifs.valorisation_laboratoire
									from objectifs
									join categories on categories.id = objectifs.categorie_id 
									join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id 
									join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id 
									join produits on produits.id = categorie_produit.produit_id
									left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY')
									join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id 
									join cliniques on cliniques.id = centrale_clinique.clinique_id
									left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
									left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
									left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
									where objectifs.obsolete is false 
									and objectifs.id = " . $objectifId . "
									and cliniques.id = " . $cliniqueId . "
									and cliniques.obsolete IS FALSE
								) achats_periode
							) achats
							group by obj_id
						) calcul_periode"), function ($join) {
				$join->on("calcul_periode.obj_id", "=", "objectifs.id");
			})
			->where('objectifs.id', '=', $objectifId);

		return $query->first();
	}
}
