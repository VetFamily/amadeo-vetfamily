<?php

namespace App\Repositories;

use DB;

class EtatsObjectifRepository implements EtatsObjectifRepositoryInterface
{
	private $relevant_objectifs_filter =
	'o.obsolete IS FALSE 
	AND o.suivi IS TRUE 
	AND o.type_valorisation_objectif_id IS NOT NULL 
	AND o.type_objectif_id IS NOT NULL';


	public function findEtatNonAtteintByEcart($ecart)
	{
		$query = "SELECT id, nom from etats_objectif where (:ecart between ecart_min and ecart_max) or (:ecart > ecart_min and ecart_max is null) or (ecart_min is null and :ecart < ecart_max)";
		return DB::select(DB::raw($query), ['ecart' => $ecart]);
	}

	public function findAll()
	{
		$query = "SELECT * FROM etats_objectif eo WHERE eo.obsolete IS FALSE";
		$results = DB::select(DB::raw($query));
		$tobeReplaced = ['{min}', '{max}'];
		foreach ($results as $result) {
			$replacing = [$result->ecart_min,  $result->ecart_max];
			$result->detail = str_replace($tobeReplaced, $replacing, $result->detail);
		}
		return $results;
	}

	public function findByYear(int $year)
	{
		$query = "SELECT sum(atteint) AS atteint, 
					sum(atteint_condition_ko) AS atteint_condition_ko, 
					sum(securite) AS securite, 
					sum(ligne_plus) AS ligne_plus, 
					sum(ligne_moins) AS ligne_moins, 
					sum(danger) AS danger
				FROM
				(
					SELECT o.id,
						(CASE o.etat_objectif_id WHEN 1 THEN 1 ELSE 0 END) AS atteint,
						(CASE o.etat_objectif_id WHEN 2 THEN 1 ELSE 0 END) AS atteint_condition_ko,
						(CASE o.etat_objectif_id WHEN 3 THEN 1 ELSE 0 END) AS securite,
						(CASE o.etat_objectif_id WHEN 4 THEN 1 ELSE 0 END) AS ligne_plus,
						(CASE o.etat_objectif_id WHEN 5 THEN 1 ELSE 0 END) AS ligne_moins,
						(CASE o.etat_objectif_id WHEN 6 THEN 1 ELSE 0 END) AS danger
					FROM objectifs o

					JOIN categories cat ON cat.id = o.categorie_id
					WHERE {$this->relevant_objectifs_filter}
					AND cat.annee = :year
				) t";
		return DB::select(DB::raw($query), ['year' => $year]);
	}

	public function findByYearAndState(int $year, string $stateId)
	{
		$query = "SELECT
					o.id as id,
					liste_especes.especes,
					l.nom as lab_nom,
					o.nom,
					o.valeur,
					o.valeur_ca as avancement,
					o.ecart
				from
					objectifs o
					join etats_objectif eo ON
					eo.id = o.etat_objectif_id
				join categories cat on
					cat.id = o.categorie_id
				left join laboratoires l on
					l.id = cat.laboratoire_id
				left join (
					select
						ce.categorie_id,
						string_agg(e.nom::character varying(100), ', ' order by e.nom) as especes
					from
						categorie_espece ce
					join especes e on
						e.id = ce.espece_id
					group by
						ce.categorie_id ) liste_especes on
					liste_especes.categorie_id = cat.id
				where
					{$this->relevant_objectifs_filter}
					and eo.nom = :stateId
					and cat.annee = :year;";
		return DB::select(DB::raw($query),  ['year' => $year, 'stateId' => $stateId]);
	}

	public function findMonthlySesonalityByObjectifId($id)
	{
		$query = "SELECT liste_mois.mois,
					(case
						when ca_periode_annee_n is not null then ca_periode_annee_n
						else 
							case (EXTRACT(month from( select DATE(valeur) from parametrage where id =1  )))
								when 1 then  0
								else 
							case 
								when liste_mois.mois < EXTRACT(month from( select DATE(valeur) from parametrage where id =1  )) then 0
								else null
							end 
						end 
					end) as ca_periode_annee_n,  
					(case 
						when ca_periode_annee_prec_n is not null then ca_periode_annee_prec_n
						else 0
					end)as ca_periode_annee_prec_n
				
				from 
				(
					SELECT generate_series((select extract (month from date_debut) from objectifs where id = :id)::integer , 
					(select extract (month from date_fin) from objectifs where id = :id) ::integer)as mois 
				) as liste_mois
				left join
				(
					select  EXTRACT(month from date) as mois,
					round(sum((CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN
										CASE valorisation_laboratoire 
											WHEN 'Valorisation en euros' THEN (valo_euro * volume)
											WHEN 'Valorisation en volume' THEN (valo_volume * volume)
											ELSE (valo_euro * volume)
										END 
									ELSE NULL 
								END))::numeric,2) AS ca_periode_annee_n
					from(
						select distinct o.id AS obj_id, a.id, a.date AS date, a.ca_complet, a.qte_payante_complet AS volume, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume, cpo.pourcentage_remise, o.type_valorisation_objectif_id, o.valorisation_laboratoire
						from objectifs o
						join categories cat on cat.id = o.categorie_id 
						join categorie_produit_objectif cpo on cpo.objectif_id = o.id 
						join categorie_produit cpr on cpr.id = cpo.categorie_produit_id 
						join produits p on p.id = cpr.produit_id
						left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and (a.date between to_date('01/' || EXTRACT(MONTH from o.date_debut) || '/' || cat.annee, 'DD/MM/YYYY') and last_day(to_date('01/' || EXTRACT(MONTH from o.date_fin) || '/' || cat.annee, 'DD/MM/YYYY')))
						join centrale_clinique cc on cc.id = a.centrale_clinique_id 
						join cliniques c on c.id = cc.clinique_id
						left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
						left join centrale_produit cp on cp.id = a.centrale_produit_id
						left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
						where 
						{$this->relevant_objectifs_filter}
						and EXTRACT(YEAR from c.date_entree) < (cat.annee + 1)
						and c.obsolete is false
						and o.id = :id 
					) as t
					group by EXTRACT(month from date)
				) as annee_n on liste_mois.mois = annee_n.mois  
				left join    
				(
					select  EXTRACT(month from date) as mois,
						round(sum((CASE type_valorisation_objectif_id 
									WHEN 1 THEN ca_complet 
									WHEN 2 THEN (prix_unitaire_hors_promo * volume)
									WHEN 3 THEN
										CASE valorisation_laboratoire 
											WHEN 'Valorisation en euros' THEN (valo_euro * volume)
											WHEN 'Valorisation en volume' THEN (valo_volume * volume)
											ELSE (valo_euro * volume)
										END 
									ELSE NULL 
								END))::numeric,2) AS ca_periode_annee_prec_n
					from
					(
						select distinct o.id AS obj_id, a.id, a.date AS date, a.ca_complet, a.qte_payante_complet AS volume, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume, cpo.pourcentage_remise, o.type_valorisation_objectif_id, o.valorisation_laboratoire
						from objectifs o
						join categories cat on cat.id = o.categorie_id 
						join categorie_produit_objectif cpo on cpo.objectif_id = o.id 
						join categorie_produit cpr on cpr.id = cpo.categorie_produit_id 
						join produits p on p.id = cpr.produit_id
						left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE and (a.date between to_date('01/' || EXTRACT(MONTH from o.date_debut) || '/' || (cat.annee-1), 'DD/MM/YYYY') and last_day(to_date('01/' || EXTRACT(MONTH from o.date_fin) || '/' || (cat.annee-1), 'DD/MM/YYYY')))
						join centrale_clinique cc on cc.id = a.centrale_clinique_id 
						join cliniques c on c.id = cc.clinique_id
						left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
						left join centrale_produit cp on cp.id = a.centrale_produit_id
						left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
						where 
						{$this->relevant_objectifs_filter}
						and EXTRACT(YEAR from c.date_entree) < (cat.annee + 1)
						and c.obsolete is false
						and o.id = :id
					) as t
					group by EXTRACT(month from date)  
				)as annee_prec on annee_prec.mois = liste_mois.mois
				order by liste_mois.mois";
		return DB::select(DB::raw($query),  ['id' => $id]);
	}

	public function findEvolutionAndParticipationRatesProducts($id)
	{
		$query = "with parametrage as ( select to_date(substring(valeur, 1, 10), 'DD/MM/YYYY') as date_maj from parametrage where id = 1 )
					select prod_id , nom_produit, conditionnement, valeur_ca_obj, ca_periode_annee_n, ca_periode_annee_prec_n,
						(ca_periode_annee_n - ca_periode_annee_prec_n) as ecarts,
						(case 
							when (ca_periode_annee_prec_n is not null and ca_periode_annee_prec_n != 0) then round((((ca_periode_annee_n - ca_periode_annee_prec_n)/ca_periode_annee_prec_n) * 100)::numeric, 2)
							else null
						end) as pourc_fonc_annee_prec,
						(case 
							when pourc_fonc_annee_prec > 0 then pourc_fonc_annee_prec
							else null
						end) as pourc_fonc_annee_prec_positif,
						(case 
							when pourc_fonc_annee_prec < 0 then pourc_fonc_annee_prec
							else null
						end) as pourc_fonc_annee_prec_negatif,
						round(( (ca_periode_annee_n/valeur_ca_obj) * 100)::numeric, 2) as pourc_fonc_obj
					from 
					(
						select annee.prod_id, annee.nom_produit, annee.conditionnement, annee.valeur_ca_obj, annee.ca_periode as ca_periode_annee_n, annee_prec.ca_periode as ca_periode_annee_prec_n,
						(case 
							when coalesce(annee_prec.ca_periode, annee.ca_periode) != 0 
							then round((((annee.ca_periode - annee_prec.ca_periode)/coalesce(annee_prec.ca_periode, annee.ca_periode)) * 100)::numeric, 2)
							else null
						end) as pourc_fonc_annee_prec	
						from
						(	
							select prod_id , nom_produit, conditionnement, valeur_ca_obj, 
								coalesce(round(sum(
									case type_valorisation_objectif_id 
										when 1 then ca_complet 
										when 2 then (prix_unitaire_hors_promo * volume) 
										when 3 then 
											case valorisation_laboratoire 
												when 'Valorisation en euros' then (valo_euro * volume) 
												when 'Valorisation en volume' then (valo_volume * volume) 
												else (valo_euro * volume) 
											end 
										else null 
									end)::numeric, 2
								), 0) as ca_periode
							from
							(
								select distinct o.id as obj_id, o.valeur_ca as valeur_ca_obj, p.id as prod_id, p.denomination as nom_produit, p.conditionnement, cat.annee,
									a.id, a.date as date, a.ca_complet, a.qte_payante_complet as volume, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume,
									cpo.pourcentage_remise, o.type_valorisation_objectif_id, o.valorisation_laboratoire
								from objectifs o
								join categories cat on cat.id = o.categorie_id
								join categorie_produit_objectif cpo on cpo.objectif_id = o.id
								join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
								join produits p on p.id = cpr.produit_id
								left outer join achats a on a.produit_id = p.id
									and a.obsolete is false
									and a.date between o.date_debut and o.date_fin
									and extract(year from a.date) = cat.annee
								join centrale_clinique cc on cc.id = a.centrale_clinique_id
								join cliniques c on c.id = cc.clinique_id
									and extract(year from c.date_entree) <= cat.annee
									and c.obsolete is false
								left join produit_valorisations pv on pv.produit_id = p.id
									and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
								left join centrale_produit cp on cp.id = a.centrale_produit_id
								left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id
									and a.date = cpt.date_creation
									and cpt.qte_tarif::numeric = 1
								where o.id = :id
							) as t
							group by prod_id , nom_produit, conditionnement, valeur_ca_obj
						) as annee
						left join (	
							select prod_id , nom_produit, conditionnement,
								coalesce(round(sum(
									case type_valorisation_objectif_id 
										when 1 then ca_complet 
										when 2 then (prix_unitaire_hors_promo * volume) 
										when 3 then 
											case valorisation_laboratoire 
												when 'Valorisation en euros' then (valo_euro * volume) 
												when 'Valorisation en volume' then (valo_volume * volume) 
												else (valo_euro * volume) 
											end 
										else null 
									end)::numeric, 2
								), 0) as ca_periode
							from
							(
								select distinct o.id as obj_id, p.id as prod_id, p.denomination as nom_produit, p.conditionnement, c.nom as nom_clinique, cat.annee,
									a.id, a.date as date, a.ca_complet, a.qte_payante_complet as volume, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume,
									cpo.pourcentage_remise, o.type_valorisation_objectif_id, o.valorisation_laboratoire
								from objectifs o
								join categories cat on cat.id = o.categorie_id
								join categorie_produit_objectif cpo on cpo.objectif_id = o.id
								join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
								join produits p on p.id = cpr.produit_id
								left outer join achats a on a.produit_id = p.id
									and a.obsolete is false
									and a.date between (o.date_debut - interval '1 year')::date and (o.date_fin - interval '1 year')::date
									and extract(year from a.date) = (cat.annee-1)
									and a.date < (select (date_maj - interval '1 year')::date from parametrage)
								join centrale_clinique cc on cc.id = a.centrale_clinique_id
								join cliniques c on c.id = cc.clinique_id
									and extract(year from c.date_entree) <= cat.annee
									and c.obsolete is false
								left join produit_valorisations pv on pv.produit_id = p.id
									and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
								left join centrale_produit cp on cp.id = a.centrale_produit_id
								left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id
									and a.date = cpt.date_creation
									and cpt.qte_tarif::numeric = 1
								where o.id = :id
							) as t
							group by prod_id , nom_produit, conditionnement
						) as annee_prec on annee_prec.prod_id = annee.prod_id
					) t";
		return DB::select(DB::raw($query),  ['id' => $id]);
	}

	public function findEvolutionAndParticipationRatesClinics($id)
	{
		$query = "with parametrage as ( select to_date(substring(valeur, 1, 10), 'DD/MM/YYYY') as date_maj from parametrage where id = 1 )
					select id_clinique, veterinaires, nom_clinique, valeur_ca_obj, ca_periode_annee_n, ca_periode_annee_prec_n,
						(ca_periode_annee_n - ca_periode_annee_prec_n) as ecarts,
						(case 
							when (ca_periode_annee_prec_n is not null and ca_periode_annee_prec_n != 0) then round((((ca_periode_annee_n - ca_periode_annee_prec_n)/ca_periode_annee_prec_n) * 100)::numeric, 2)
							else null
						end) as pourc_fonc_annee_prec,
						(case 
							when pourc_fonc_annee_prec > 0 then pourc_fonc_annee_prec
							else null
						end) as pourc_fonc_annee_prec_positif,
						(case 
							when pourc_fonc_annee_prec < 0 then pourc_fonc_annee_prec
							else null
						end) as pourc_fonc_annee_prec_negatif,
						round(( (ca_periode_annee_n/valeur_ca_obj) * 100)::numeric, 2) as pourc_fonc_obj
					from 
					(
						select annee.id_clinique, annee.veterinaires, annee.nom_clinique, annee.valeur_ca_obj, annee.ca_periode as ca_periode_annee_n, annee_prec.ca_periode as ca_periode_annee_prec_n,
						(case 
							when coalesce(annee_prec.ca_periode, annee.ca_periode) != 0 
							then round((((annee.ca_periode - annee_prec.ca_periode)/coalesce(annee_prec.ca_periode, annee.ca_periode)) * 100)::numeric, 2)
							else null
						end) as pourc_fonc_annee_prec	
						from
						(	
							select id_clinique, veterinaires, nom_clinique, valeur_ca_obj, 
								coalesce(round(sum(
									case type_valorisation_objectif_id 
										when 1 then ca_complet 
										when 2 then (prix_unitaire_hors_promo * volume) 
										when 3 then 
											case valorisation_laboratoire 
												when 'Valorisation en euros' then (valo_euro * volume) 
												when 'Valorisation en volume' then (valo_volume * volume) 
												else (valo_euro * volume) 
											end 
										else null 
									end)::numeric, 2
								), 0) as ca_periode
							from
							(
								select distinct o.id as obj_id, o.valeur_ca as valeur_ca_obj, c.id as id_clinique, c.veterinaires as veterinaires , c.nom as nom_clinique, cat.annee,
									a.id, a.date as date, a.ca_complet, a.qte_payante_complet as volume, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume,
									cpo.pourcentage_remise, o.type_valorisation_objectif_id, o.valorisation_laboratoire
								from objectifs o
								join categories cat on cat.id = o.categorie_id
								join categorie_produit_objectif cpo on cpo.objectif_id = o.id
								join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
								join produits p on p.id = cpr.produit_id
								left outer join achats a on a.produit_id = p.id
									and a.obsolete is false
									and a.date between o.date_debut and o.date_fin
									and extract(year from a.date) = cat.annee
								join centrale_clinique cc on cc.id = a.centrale_clinique_id
								join cliniques c on c.id = cc.clinique_id
									and extract(year from c.date_entree) <= cat.annee
									and c.obsolete is false
								left join produit_valorisations pv on pv.produit_id = p.id
									and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
								left join centrale_produit cp on cp.id = a.centrale_produit_id
								left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id
									and a.date = cpt.date_creation
									and cpt.qte_tarif::numeric = 1
								where o.id = :id
							) as t
							group by id_clinique, veterinaires, nom_clinique, valeur_ca_obj
						) as annee
						left join (	
							select id_clinique, veterinaires, nom_clinique,
								coalesce(round(sum(
									case type_valorisation_objectif_id 
										when 1 then ca_complet 
										when 2 then (prix_unitaire_hors_promo * volume) 
										when 3 then 
											case valorisation_laboratoire 
												when 'Valorisation en euros' then (valo_euro * volume) 
												when 'Valorisation en volume' then (valo_volume * volume) 
												else (valo_euro * volume) 
											end 
										else null 
									end)::numeric, 2
								), 0) as ca_periode
							from
							(
								select distinct o.id as obj_id, c.id as id_clinique, c.veterinaires as veterinaires , c.nom as nom_clinique, cat.annee,
									a.id, a.date as date, a.ca_complet, a.qte_payante_complet as volume, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume,
									cpo.pourcentage_remise, o.type_valorisation_objectif_id, o.valorisation_laboratoire
								from objectifs o
								join categories cat on cat.id = o.categorie_id
								join categorie_produit_objectif cpo on cpo.objectif_id = o.id
								join categorie_produit cpr on cpr.id = cpo.categorie_produit_id
								join produits p on p.id = cpr.produit_id
								left outer join achats a on a.produit_id = p.id
									and a.obsolete is false
									and a.date between (o.date_debut - interval '1 year')::date and (o.date_fin - interval '1 year')::date
									and extract(year from a.date) = (cat.annee-1)
									and a.date < (select (date_maj - interval '1 year')::date from parametrage)
								join centrale_clinique cc on cc.id = a.centrale_clinique_id
								join cliniques c on c.id = cc.clinique_id
									and extract(year from c.date_entree) <= cat.annee
									and c.obsolete is false
								left join produit_valorisations pv on pv.produit_id = p.id
									and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
								left join centrale_produit cp on cp.id = a.centrale_produit_id
								left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id
									and a.date = cpt.date_creation
									and cpt.qte_tarif::numeric = 1
								where o.id = :id
							) as t
							group by id_clinique, veterinaires, nom_clinique
						) as annee_prec on annee_prec.id_clinique = annee.id_clinique
					) t";
		return DB::select(DB::raw($query),  ['id' => $id]);
	}
}
