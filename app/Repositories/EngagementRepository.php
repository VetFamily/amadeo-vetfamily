<?php

namespace App\Repositories;

use App\Model\Engagement;
use DB;
use Illuminate\Support\Facades\Log;

class EngagementRepository implements EngagementRepositoryInterface
{
    protected $engagement;

	public function __construct(Engagement $engagement)
	{
		$this->engagement = $engagement;
	}

	public function findAll($cliniqueId)
	{
		if ($cliniqueId != null)
		{
			$query = "
				select distinct engagements.id, objectifs.id as objectif_id, objectifs.nom as objectif, objectifs.valeur as valeur_obj, objectifs.type_objectif_id AS type_obj, EXTRACT(MONTH from objectifs.date_debut) AS mois_debut, EXTRACT(MONTH from objectifs.date_fin) AS mois_fin, categories.annee as annee, categories.nom as categorie, laboratoires.nom as laboratoire, liste_especes.especes, engagements.valeur as valeur_eng, engagements.valeur_auto,
				(CASE objectifs.type_valorisation_objectif_id 
					WHEN 1 THEN coalesce(calcul_periode.ca_total_achat, 0) 
					WHEN 2 THEN coalesce(calcul_periode.ca_total_centrale, 0) 
					WHEN 3 THEN 
						CASE objectifs.valorisation_laboratoire 
							WHEN 'Valorisation en euros' THEN coalesce(calcul_periode.ca_total_labo_euro, 0)
							WHEN 'Valorisation en volume' THEN coalesce(calcul_periode.ca_total_labo_vol, 0)
							ELSE coalesce(calcul_periode.ca_total_labo_euro, 0)
						END 
					ELSE NULL 
				END) AS ca_periode, 
				(CASE objectifs.type_valorisation_objectif_id 
					WHEN 1 THEN coalesce(calcul_periode_prec.ca_total_achat, 0) 
					WHEN 2 THEN coalesce(calcul_periode_prec.ca_total_centrale, 0) 
					WHEN 3 THEN 
						CASE objectifs.valorisation_laboratoire 
							WHEN 'Valorisation en euros' THEN coalesce(calcul_periode_prec.ca_total_labo_euro, 0)
							WHEN 'Valorisation en volume' THEN coalesce(calcul_periode_prec.ca_total_labo_vol, 0)
							ELSE coalesce(calcul_periode_prec.ca_total_labo_euro, 0)
						END 
					ELSE NULL 
				END) AS ca_periode_prec, 
				(CASE objectifs.valorisation_laboratoire 
					WHEN 'Valorisation en euros' THEN '€'
					WHEN 'Valorisation en volume' THEN liste_produits.unite_valo_volume
					ELSE '€'
				END) AS unite_valo_volume
				from engagements 
				join objectifs on objectifs.id = engagements.objectif_id
				join categories on categories.id = objectifs.categorie_id 
				left join laboratoires on laboratoires.id = categories.laboratoire_id 
				left join (
					select categorie_espece.categorie_id, string_agg(especes.nom::character varying (60), ', ' order by especes.nom) AS especes
					from categorie_espece
					join especes on especes.id = categorie_espece.espece_id
					group by categorie_espece.categorie_id
				) liste_especes on liste_especes.categorie_id = categories.id 
				left join (
					select categorie_produit_objectif.objectif_id, produits.unite_valo_volume
					from categorie_produit_objectif
					join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
					join produits on produits.id = categorie_produit.produit_id
				) liste_produits on liste_produits.objectif_id = objectifs.id
				left join (
					select obj_id, round(sum(ca_complet)::numeric,2) AS ca_total_achat, round(sum(valo_euro * volume)::numeric,2) AS ca_total_labo_euro, round(sum(valo_volume * volume)::numeric,2) AS ca_total_labo_vol, round(sum(prix_unitaire_hors_promo * volume)::numeric,2) AS ca_total_centrale
					FROM (
						select distinct objectifs.id AS obj_id, achats.id, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume
						from objectifs
						join categories on categories.id = objectifs.categorie_id
						join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
						join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
						join produits on produits.id = categorie_produit.produit_id
						left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY'))
						join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
						left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
						left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
						left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
						where objectifs.obsolete IS FALSE 
						and objectifs.suivi IS TRUE
						and objectifs.sans_engagement IS FALSE
						and objectifs.type_valorisation_objectif_id is not null
						and categories.annee > 2017
						and centrale_clinique.clinique_id = " . $cliniqueId . " 
					) achats_periode
					group by obj_id
				) calcul_periode on calcul_periode.obj_id = objectifs.id 
				left join (
					select obj_id, round(sum(ca_complet)::numeric,2) AS ca_total_achat, round(sum(valo_euro * volume)::numeric,2) AS ca_total_labo_euro, round(sum(valo_volume * volume)::numeric,2) AS ca_total_labo_vol, round(sum(prix_unitaire_hors_promo * volume)::numeric,2) AS ca_total_centrale
					FROM (
						select distinct objectifs.id AS obj_id, achats.id, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume
						from objectifs
						join categories on categories.id = objectifs.categorie_id
						join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
						join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
						join produits on produits.id = categorie_produit.produit_id
						left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || (categories.annee - 1), 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || (categories.annee - 1), 'DD/MM/YYYY'))
						join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
						left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
						left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
						left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
						where objectifs.obsolete IS FALSE 
						and objectifs.suivi IS TRUE
						and objectifs.sans_engagement IS FALSE
						and objectifs.type_valorisation_objectif_id is not null
						and categories.annee > 2017
						and centrale_clinique.clinique_id = " . $cliniqueId . " 
					) achats_periode
					group by obj_id
				) calcul_periode_prec on calcul_periode_prec.obj_id = objectifs.id 
				where objectifs.obsolete IS FALSE 
				and objectifs.suivi IS TRUE
				and objectifs.sans_engagement IS FALSE
				and objectifs.type_valorisation_objectif_id is not null
				and categories.annee > 2017
				and engagements.clinique_id = " . $cliniqueId;
		} else
		{
			$query = "
				select distinct objectifs.id as id, objectifs.id as objectif_id, objectifs.nom as objectif, objectifs.valeur as valeur_obj, objectifs.valeur_ca AS ca_periode, objectifs.valeur_ca_total_prec AS ca_periode_prec, objectifs.type_objectif_id AS type_obj, EXTRACT(MONTH from objectifs.date_debut) AS mois_debut, EXTRACT(MONTH from objectifs.date_fin) AS mois_fin, categories.annee as annee, categories.nom as categorie, laboratoires.nom as laboratoire, liste_especes.especes, liste_engagements.valeur_eng, false as valeur_auto,
				(CASE objectifs.valorisation_laboratoire 
					WHEN 'Valorisation en euros' THEN '€'
					WHEN 'Valorisation en volume' THEN liste_produits.unite_valo_volume
					ELSE '€'
				END) AS unite_valo_volume
				from objectifs 
				join categories on categories.id = objectifs.categorie_id 
				left join laboratoires on laboratoires.id = categories.laboratoire_id 
				left join (
					select categorie_espece.categorie_id, string_agg(especes.nom::character varying (60), ', ' order by especes.nom) AS especes
					from categorie_espece
					join especes on especes.id = categorie_espece.espece_id
					group by categorie_espece.categorie_id
				) liste_especes on liste_especes.categorie_id = categories.id 
				left join (
					select categorie_produit_objectif.objectif_id, produits.unite_valo_volume
					from categorie_produit_objectif
					join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
					join produits on produits.id = categorie_produit.produit_id
				) liste_produits on liste_produits.objectif_id = objectifs.id
				left join (
					select obj_id, sum(valeur_eng) AS valeur_eng
					from 
					(
						select objectifs.id as obj_id, engagements.id, engagements.valeur as valeur_eng
						from engagements
						join objectifs on objectifs.id = engagements.objectif_id
						join categories on categories.id = objectifs.categorie_id 
						where objectifs.obsolete IS FALSE 
						and objectifs.suivi IS TRUE
						and objectifs.sans_engagement IS FALSE
						and objectifs.type_valorisation_objectif_id is not null
						and categories.annee > 2017
					) obj
					group by obj_id
				) liste_engagements on liste_engagements.obj_id = objectifs.id
				where objectifs.obsolete IS FALSE 
				and objectifs.suivi IS TRUE
				and objectifs.sans_engagement IS FALSE
				and objectifs.type_valorisation_objectif_id is not null
				and categories.annee > 2017";
		}

		return DB::select(DB::raw($query));
	}

	public function findById($id)
	{
		$result = DB::table('engagements AS e')
					->select('e.id', 'o.id as objectif_id', 'o.nom as objectif', 'e.clinique_id', 'e.valeur', 'e.valeur_auto', 'o.type_objectif_id')
					->join('objectifs AS o', 'o.id', '=', 'e.objectif_id')
					->where('e.id', $id)
					->first();

		return $result;
	}

	/**
     * Returns the list of the clinic's commitments by target id.
     */
    public function findDetailClinicsByTargetId($targetId)
	{
		$query = "
			select c.veterinaires, c.nom, c.ville, c.engagements_valides, e.valeur as valeur_eng, e.valeur_auto,
			(CASE o.type_valorisation_objectif_id 
				WHEN 1 THEN coalesce(calcul_periode.ca_total_achat, 0) 
				WHEN 2 THEN coalesce(calcul_periode.ca_total_centrale, 0) 
				WHEN 3 THEN 
					CASE o.valorisation_laboratoire 
						WHEN 'Valorisation en euros' THEN coalesce(calcul_periode.ca_total_labo_euro, 0)
						WHEN 'Valorisation en volume' THEN coalesce(calcul_periode.ca_total_labo_vol, 0)
						ELSE coalesce(calcul_periode.ca_total_labo_euro, 0)
					END 
				ELSE NULL 
			END) AS ca_periode, 
			(CASE o.type_valorisation_objectif_id 
				WHEN 1 THEN coalesce(calcul_periode_prec.ca_total_achat, 0) 
				WHEN 2 THEN coalesce(calcul_periode_prec.ca_total_centrale, 0) 
				WHEN 3 THEN 
					CASE o.valorisation_laboratoire 
						WHEN 'Valorisation en euros' THEN coalesce(calcul_periode_prec.ca_total_labo_euro, 0)
						WHEN 'Valorisation en volume' THEN coalesce(calcul_periode_prec.ca_total_labo_vol, 0)
						ELSE coalesce(calcul_periode_prec.ca_total_labo_euro, 0)
					END 
				ELSE NULL 
			END) AS ca_periode_prec
			from objectifs o 
			join engagements e on e.objectif_id = o.id 
			join cliniques c on c.id = e.clinique_id 
			left join (
				select obj_id, clinique_id, round(sum(ca_complet)::numeric,2) AS ca_total_achat, round(sum(valo_euro * volume)::numeric,2) AS ca_total_labo_euro, round(sum(valo_volume * volume)::numeric,2) AS ca_total_labo_vol, round(sum(prix_unitaire_hors_promo * volume)::numeric,2) AS ca_total_centrale
				FROM (
					select distinct o.id AS obj_id, cc.clinique_id, a.id, a.ca_complet, a.qte_payante_complet AS volume, pv.valo_euro, cept.prix_unitaire_hors_promo, p.valo_volume
					from objectifs o
					join categories c on c.id = o.categorie_id
					join categorie_produit_objectif cpo on cpo.objectif_id = o.id
					join categorie_produit cp on cp.id = cpo.categorie_produit_id
					join produits p on p.id = cp.produit_id
					left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and (a.date between to_date('01/' || EXTRACT(MONTH from o.date_debut) || '/' || c.annee, 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from o.date_fin) || '/' || c.annee, 'DD/MM/YYYY'))
					join centrale_clinique cc on cc.id = a.centrale_clinique_id
					left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
					left join centrale_produit cep on cep.id = a.centrale_produit_id
					left join centrale_produit_tarifs cept on cept.centrale_produit_id = cep.id and a.date = cept.date_creation and cept.qte_tarif::numeric = 1
					where o.id = " . $targetId . "
				) achats_periode
				group by obj_id, clinique_id
			) calcul_periode on calcul_periode.obj_id = o.id and calcul_periode.clinique_id = c.id
			left join (
				select obj_id, clinique_id, round(sum(ca_complet)::numeric,2) AS ca_total_achat, round(sum(valo_euro * volume)::numeric,2) AS ca_total_labo_euro, round(sum(valo_volume * volume)::numeric,2) AS ca_total_labo_vol, round(sum(prix_unitaire_hors_promo * volume)::numeric,2) AS ca_total_centrale
				FROM (
					select distinct o.id AS obj_id, cc.clinique_id, a.id, a.ca_complet, a.qte_payante_complet AS volume, pv.valo_euro, cept.prix_unitaire_hors_promo, p.valo_volume
					from objectifs o
					join categories c on c.id = o.categorie_id
					join categorie_produit_objectif cpo on cpo.objectif_id = o.id
					join categorie_produit cp on cp.id = cpo.categorie_produit_id
					join produits p on p.id = cp.produit_id
					left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and (a.date between to_date('01/' || EXTRACT(MONTH from o.date_debut) || '/' || (c.annee - 1), 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from o.date_fin) || '/' || (c.annee - 1), 'DD/MM/YYYY'))
					join centrale_clinique cc on cc.id = a.centrale_clinique_id
					left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
					left join centrale_produit cep on cep.id = a.centrale_produit_id
					left join centrale_produit_tarifs cept on cept.centrale_produit_id = cep.id and a.date = cept.date_creation and cept.qte_tarif::numeric = 1
					where o.id = " . $targetId . " 
				) achats_periode
				group by obj_id, clinique_id
			) calcul_periode_prec on calcul_periode_prec.obj_id = o.id and calcul_periode_prec.clinique_id = c.id
			where o.id = " . $targetId;

		$result = DB::select(DB::raw($query));

		return $result;
	}

	public function findAllForExportBilan($cliniqueId, $annee)
	{
		$query = "select objectif, objectif_id, valeur_eng, type_obj, obj_precedent, obj_conditionne, laboratoire, laboratoire_id, especes, ca_periode
				from (
					(select engagements.id as id, objectifs.id as objectif_id, objectifs.nom as objectif, engagements.valeur as valeur_eng, objectifs.type_objectif_id AS type_obj, objectifs.objectif_precedent_id AS obj_precedent, objectifs.objectif_conditionne_id AS obj_conditionne, laboratoires.nom as laboratoire, laboratoires.id as laboratoire_id, liste_especes.especes, calcul_periode.ca_periode AS ca_periode
					from engagements
					join objectifs on objectifs.id = engagements.objectif_id
					join categories on categories.id = objectifs.categorie_id 
					left join laboratoires on laboratoires.id = categories.laboratoire_id 
					left join (
						select categorie_espece.categorie_id, string_agg(especes.nom::character varying(60), '|') AS especes
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
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/" . $annee . "', 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/" . $annee . "', 'DD/MM/YYYY')
							and objectifs.obsolete IS FALSE 
							and categories.annee = " . $annee . " 
							and objectifs.type_valorisation_objectif_id = 1
							and centrale_clinique.clinique_id = " . $cliniqueId . " 
						) achats_periode
						group by obj_id
					) calcul_periode on calcul_periode.obj_id = objectifs.id 
					where objectifs.obsolete IS FALSE 
					and categories.annee = " . $annee . " 
					and engagements.clinique_id = " . $cliniqueId . " 
					and objectifs.suivi IS TRUE
					and objectifs.sans_engagement IS FALSE
					and objectifs.type_valorisation_objectif_id = 1)

					UNION 

					(select engagements.id as id, objectifs.id as objectif_id, objectifs.nom as objectif, engagements.valeur as valeur_eng, objectifs.type_objectif_id AS type_obj, objectifs.objectif_precedent_id AS obj_precedent, objectifs.objectif_conditionne_id AS obj_conditionne, laboratoires.nom as laboratoire, laboratoires.id as laboratoire_id, liste_especes.especes, calcul_periode.ca_periode AS ca_periode
					from engagements
					join objectifs on objectifs.id = engagements.objectif_id
					join categories on categories.id = objectifs.categorie_id 
					left join laboratoires on laboratoires.id = categories.laboratoire_id 
					left join (
						select categorie_espece.categorie_id, string_agg(especes.nom::character varying(60), '|') AS especes
						from categorie_espece
						join especes on especes.id = categorie_espece.espece_id
						group by categorie_espece.categorie_id
					) liste_especes on liste_especes.categorie_id = categories.id 
					left join (
						select obj_id, round(sum(vol_periode * prix_unitaire_hors_promo)::numeric,2) AS ca_periode
						FROM (
							select distinct objectifs.id AS obj_id, achats.id, achats.qte_payante_complet AS vol_periode, cpt.prix_unitaire_hors_promo
							from objectifs
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join achats on achats.produit_id = categorie_produit.produit_id and achats.obsolete IS FALSE
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							join centrale_produit on centrale_produit.centrale_id = centrale_clinique.centrale_id and centrale_produit.produit_id = categorie_produit.produit_id
							join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/" . $annee . ", 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/" . $annee . ", 'DD/MM/YYYY')
							and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							and objectifs.obsolete IS FALSE 
							and categories.annee = " . $annee . " 
							and objectifs.type_valorisation_objectif_id = 2 
							and centrale_clinique.clinique_id = " . $cliniqueId . " 
						) achats_periode
						group by obj_id
					) calcul_periode on calcul_periode.obj_id = objectifs.id 
					where objectifs.obsolete IS FALSE 
					and categories.annee = " . $annee . " 
					and engagements.clinique_id = " . $cliniqueId . " 
					and objectifs.suivi IS TRUE
					and objectifs.sans_engagement IS FALSE
					and objectifs.type_valorisation_objectif_id = 2)

					UNION 

					(select engagements.id as id, objectifs.id as objectif_id, objectifs.nom as objectif, engagements.valeur as valeur_eng, objectifs.type_objectif_id AS type_obj, objectifs.objectif_precedent_id AS obj_precedent, objectifs.objectif_conditionne_id AS obj_conditionne, laboratoires.nom as laboratoire, laboratoires.id as laboratoire_id, liste_especes.especes, 
						(CASE objectifs.valorisation_laboratoire 
						 	WHEN 'Valorisation en euros' THEN calcul_periode.ca_periode_valo1
						 	WHEN 'Valorisation en volume' THEN calcul_periode.ca_periode_valo2
						 	ELSE NULL
						 END) AS ca_periode
					from engagements
					join objectifs on objectifs.id = engagements.objectif_id
					join categories on categories.id = objectifs.categorie_id 
					left join laboratoires on laboratoires.id = categories.laboratoire_id 
					left join (
						select categorie_espece.categorie_id, string_agg(especes.nom::character varying(60), '|') AS especes
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
							join produit_valorisations on produit_valorisations.produit_id = categorie_produit.produit_id
							join produits on produits.id = categorie_produit.produit_id
							where achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/" . $annee . ", 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/" . $annee . ", 'DD/MM/YYYY')
							and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
							and objectifs.obsolete IS FALSE 
							and categories.annee = " . $annee . " 
							and objectifs.type_valorisation_objectif_id = 3 
							and centrale_clinique.clinique_id = " . $cliniqueId . " 
						) achats_periode
						group by obj_id
					) calcul_periode on calcul_periode.obj_id = objectifs.id 
					where objectifs.obsolete IS FALSE 
					and categories.annee = " . $annee . " 
					and engagements.clinique_id = " . $cliniqueId . " 
					and objectifs.suivi IS TRUE
					and objectifs.sans_engagement IS FALSE
					and objectifs.type_valorisation_objectif_id = 3)
				) objectifs
				order by objectif_id";

        return DB::select(DB::raw($query));
	}

	/**
     * Returns the list of the clinic's discounts by laboratory.
     */
    public function findAllForDiscountsEstimation($clinicId, $year)
	{
		$query = "
				select distinct laboratoire, round(sum(remise_engagements), 2) as remise_engagements, round(sum(remise_periode), 2) as remise_periode
				from 
				(
					select laboratoires.nom as laboratoire, objectifs.id,
						(CASE objectifs.type_valorisation_objectif_id 
							WHEN 1 THEN coalesce(engagements_periode.ca_total_achat * objectifs.pourcentage_remise / 100, 0) 
							WHEN 2 THEN coalesce(engagements_periode.ca_total_centrale * objectifs.pourcentage_remise / 100, 0) 
							WHEN 3 THEN 
								CASE objectifs.valorisation_laboratoire 
									WHEN 'Valorisation en euros' THEN coalesce(engagements_periode.ca_total_labo_euro * objectifs.pourcentage_remise / 100, 0)
									WHEN 'Valorisation en volume' THEN coalesce(engagements_periode.ca_total_labo_vol * objectifs.pourcentage_remise / 100, 0)
									ELSE coalesce(engagements_periode.ca_total_labo_euro * objectifs.pourcentage_remise / 100, 0)
								END 
							ELSE NULL 
						END) AS remise_periode, 
						(CASE objectifs.type_valorisation_objectif_id 
							WHEN 1 THEN coalesce(engagements.valeur * objectifs.pourcentage_remise / 100, 0) 
							WHEN 2 THEN coalesce(engagements.valeur * objectifs.pourcentage_remise / 100, 0) 
							WHEN 3 THEN 
								CASE objectifs.valorisation_laboratoire 
									WHEN 'Valorisation en euros' THEN coalesce(engagements.valeur * objectifs.pourcentage_remise / 100, 0)
									WHEN 'Valorisation en volume' THEN coalesce(engagements_valorisations.valo_moyenne * engagements.valeur * objectifs.pourcentage_remise / 100, 0)
									ELSE coalesce(engagements.valeur * objectifs.pourcentage_remise / 100, 0)
								END 
							ELSE NULL 
						END) AS remise_engagements
					from engagements 
					join objectifs on objectifs.id = engagements.objectif_id
					join categories on categories.id = objectifs.categorie_id 
					join laboratoires on laboratoires.id = categories.laboratoire_id
					left join 
					(
						select eng_id, avg(valo_euro / valo_volume) as valo_moyenne
						from 
						(
							select distinct engagements.id as eng_id, produits.id as prod_id, produit_valorisations.valo_euro, produits.valo_volume
							from engagements
							join objectifs on objectifs.id = engagements.objectif_id
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
							join laboratoires on laboratoires.id = produits.laboratoire_id 
							left join produit_valorisations on produit_valorisations.produit_id = produits.id and produit_valorisations.date_fin is null
							where objectifs.obsolete IS FALSE 
							and objectifs.suivi IS TRUE
							and objectifs.sans_engagement IS FALSE
							and objectifs.type_valorisation_objectif_id is not null
							and engagements.valeur != 0
							and categories.annee = " . $year . " 
						) achats
						group by eng_id
					) engagements_valorisations on engagements_valorisations.eng_id = engagements.id
					left join (
						select eng_id, 
							round(sum(ca_complet)::numeric,2) AS ca_total_achat, 
							round(sum(valo_euro * volume)::numeric,2) AS ca_total_labo_euro, 
							round(sum(volume * valo_euro / valo_volume)::numeric,2) AS ca_total_labo_vol, round(sum(prix_unitaire_hors_promo * volume)::numeric,2) AS ca_total_centrale
						FROM (
							select distinct engagements.id as eng_id, achats.id, achats.ca_complet, achats.qte_payante_complet AS volume, produit_valorisations.valo_euro, cpt.prix_unitaire_hors_promo, produits.valo_volume
							from engagements
							join objectifs on objectifs.id = engagements.objectif_id
							join categories on categories.id = objectifs.categorie_id
							join categorie_produit_objectif on categorie_produit_objectif.objectif_id = objectifs.id
							join categorie_produit on categorie_produit.id = categorie_produit_objectif.categorie_produit_id
							join produits on produits.id = categorie_produit.produit_id
							join laboratoires on laboratoires.id = produits.laboratoire_id 
							left outer join achats on achats.produit_id = produits.id and achats.obsolete IS FALSE and (achats.date between to_date('01/' || EXTRACT(MONTH from objectifs.date_debut) || '/' || categories.annee, 'DD/MM/YYYY') and to_date('28/' || EXTRACT(MONTH from objectifs.date_fin) || '/' || categories.annee, 'DD/MM/YYYY'))
							join centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
							left join produit_valorisations on produit_valorisations.produit_id = produits.id and ((achats.date between produit_valorisations.date_debut and produit_valorisations.date_fin) or (achats.date >= produit_valorisations.date_debut and produit_valorisations.date_fin is null))
							left join centrale_produit on centrale_produit.id = achats.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = centrale_produit.id and achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							where objectifs.obsolete IS FALSE 
							and objectifs.suivi IS TRUE
							and objectifs.sans_engagement IS FALSE
							and objectifs.type_valorisation_objectif_id is not null
							and engagements.valeur != 0
							and categories.annee = " . $year . " 
							and centrale_clinique.clinique_id = " . $clinicId . " 
						) achats_periode
						group by eng_id
					) engagements_periode on engagements_periode.eng_id = engagements.id 
					where objectifs.obsolete IS FALSE 
					and objectifs.suivi IS TRUE
					and objectifs.sans_engagement IS FALSE
					and objectifs.type_valorisation_objectif_id is not null
					and engagements.valeur != 0
					and categories.annee = " . $year . " 
					and engagements.clinique_id = " . $clinicId . " 
				) t
				group by laboratoire
				order by laboratoire";

		return DB::select(DB::raw($query));
	}

}