<?php

namespace App\Repositories;

use App\Model\Clinique;
use Carbon\Carbon;
use DB;

class CliniqueRepository implements CliniqueRepositoryInterface
{

    protected $clinique;

	public function __construct(Clinique $clinique)
	{
		$this->clinique = $clinique;
	}

	public function findAllForSelect($userId)
	{
		if ($userId == null)
		{
			$result = $this->clinique
							->select(['cliniques.id', 'cliniques.veterinaires as name'])
							->join('centrale_clinique', 'centrale_clinique.clinique_id', '=', 'cliniques.id')
							->where('cliniques.obsolete', '=', '0')
							->orderBy('cliniques.veterinaires')
							->distinct()
							->get()
							->toJson();
		}
		else
		{
			$result = $this->clinique->select(['cliniques.id as id', 'cliniques.veterinaires as name', DB::raw("'true' as selected")])
							->join('centrale_clinique', 'centrale_clinique.clinique_id', '=', 'cliniques.id')
							->join('role_user','role_user.clinique_id', '=', 'cliniques.id')
							->where('cliniques.obsolete', '=', '0')
							->where('role_user.user_id', '=', $userId)
							->distinct()
							->get()
							->toJson();
		}

        return $result;
	}

	public function findAll($year, $userClinicId)
	{
		$query = "
		select c.id AS clinique_id, c.veterinaires, c.nom as clinique, c.adresse, c.code_postal, c.ville, c.date_entree, c.date_left, co.ctry_name as country
		from cliniques c
		join ed_country co on co.ctry_id = c.country_id
		where c.obsolete is false
		" . (($year != null) ? "and EXTRACT(YEAR from c.date_entree) < " . ($year+1) : "") . "
		" . (($userClinicId != null) ? "and c.id = " . $userClinicId : "") . "
		order by veterinaires";

		$result = DB::select(DB::raw($query));

        return $result;
	}

	public function findDetailById($id)
	{
		$query = "
		select clinique_id, commentaire, utilisateurs, 
		json_agg(json_build_object('centrale_nom', centrale_nom, 'centrale_id', centrale_id, 'identifiant', coalesce(identifiant_hors_web, ''))) as infos_hors_web, 
		json_agg(json_build_object('centrale_nom', centrale_nom, 'centrale_id', centrale_id, 'identifiant', coalesce(identifiant_web, ''))) as infos_web
		from
		(
			select c.clinique_id, c.commentaire, c.centrale_id, c.centrale_nom, liste_centrales_hors_web.identifiant_hors_web, liste_centrales_web.identifiant_web, c.utilisateurs
			from
			(
				select c.id as clinique_id, c.commentaire, ce.id AS centrale_id, ce.nom AS centrale_nom, users.utilisateurs
				from centrales ce,
				cliniques c
				left join 
				(
					select ru.clinique_id as clinique_id, string_agg(concat(u.prenom, ' ', u.nom, ' / ', u.email), '<br/>' order by u.prenom, u.nom) as utilisateurs
					from users u
					join role_user ru on u.id = ru.user_id
					where u.obsolete is false
					and ru.role_id = 3
					group by clinique_id
				) users on users.clinique_id = c.id
				where c.id = :id
				and ce.obsolete is false
				order by ce.nom
			) c 
			left join
			(
				select cc.clinique_id AS clinique_id, cc.centrale_id, 
					string_agg(
						(case
							when cc.supplier_id is not null 
								then concat(cc.identifiant::character varying(100), ' (', l.nom, ')')
							else
								cc.identifiant::character varying(100)
						end), 
						'|' order by cc.identifiant
					) as identifiant_hors_web
				from centrales c
				left join centrale_clinique cc ON cc.centrale_id = c.id
				left join laboratoires l on l.id = cc.supplier_id and l.obsolete is false
				where cc.clinique_id = :id
				and cc.web is false
				group by clinique_id, centrale_id
			) liste_centrales_hors_web ON liste_centrales_hors_web.clinique_id = c.clinique_id AND liste_centrales_hors_web.centrale_id = c.centrale_id
			left join
			(
				select cc.clinique_id AS clinique_id, cc.centrale_id, string_agg(cc.identifiant::character varying(100), '|' order by cc.identifiant) as identifiant_web
				from centrales c
				left join centrale_clinique cc ON cc.centrale_id = c.id
				where cc.clinique_id = :id
				and cc.web is true
				group by clinique_id, centrale_id
			) liste_centrales_web ON liste_centrales_web.clinique_id = c.clinique_id AND liste_centrales_web.centrale_id = c.centrale_id
		) t
		group by clinique_id, commentaire, utilisateurs";

		$result = DB::select(DB::raw($query), ["id" => $id]);

        return $result;
	}

	public function findById($id)
	{
		$query = "select liste.nom AS clinique, liste.veterinaires, liste.ville, liste.annee, liste.clinique_id, string_agg(liste.infos, '|||') AS centrales
				from 
				(
				    select liste_cliniques.clinique_id, liste_cliniques.nom, liste_cliniques.veterinaires, liste_cliniques.ville, liste_cliniques.annee, CONCAT(liste_cliniques.centrale_id, '||', liste_cliniques.centrale_nom, '||', coalesce(liste_centrales.identifiant, ' ')) AS infos
				    from 
				    (
				        select cliniques.id AS clinique_id, cliniques.nom, cliniques.veterinaires, cliniques.ville, date_entree AS annee, centrales.id AS centrale_id, centrales.nom AS centrale_nom
				        from cliniques, centrales
				        where cliniques.obsolete is false
				        and cliniques.id = " . $id . "
				    ) liste_cliniques
				    left join
				    (
				        select centrale_clinique.clinique_id, centrale_clinique.centrale_id, centrale_clinique.identifiant
				        from centrales
				        left join centrale_clinique ON centrale_clinique.centrale_id = centrales.id
				    ) liste_centrales ON liste_centrales.clinique_id = liste_cliniques.clinique_id AND liste_centrales.centrale_id = liste_cliniques.centrale_id
				) liste
				group by liste.clinique_id, liste.nom, liste.veterinaires, liste.ville, liste.annee
				order by liste.nom";

		$result = DB::select(DB::raw($query));

        return $result;
	}

	public function findAllForExportCSV($clinicIds)
	{
		$query = "
		select clinique_id, veterinaires, clinique, adresse, code_postal, ville, date_entree, date_left, country, commentaire, 
		json_agg(json_build_object('centrale_nom', centrale_nom, 'centrale_id', centrale_id, 'identifiant', coalesce(identifiant_hors_web, ''))) as infos_hors_web, 
		json_agg(json_build_object('centrale_nom', centrale_nom, 'centrale_id', centrale_id, 'identifiant', coalesce(identifiant_web, ''))) as infos_web
		from
		(
			select c.clinique_id, c.veterinaires, c.clinique, c.adresse, c.code_postal, c.ville, c.date_entree, c.date_left, c.country, c.commentaire, c.centrale_id, c.centrale_nom, liste_centrales_hors_web.identifiant_hors_web, liste_centrales_web.identifiant_web
			from
			(
				select c.id as clinique_id, c.veterinaires, c.nom as clinique, c.adresse, c.code_postal, c.ville, c.date_entree, c.date_left, co.ctry_name as country, c.commentaire, ce.id AS centrale_id, ce.nom AS centrale_nom
				from centrales ce,
				cliniques c
				join ed_country co on co.ctry_id = c.country_id
				where c.obsolete is false
				and ce.obsolete is false
				" . ($clinicIds != null ? "c.id in (" . implode(', ', $clinicIds) . ")" : "") . "
				order by ce.nom
			) c 
			left join
			(
				select cc.clinique_id AS clinique_id, cc.centrale_id, 
					string_agg(
						(case
							when cc.supplier_id is not null 
								then concat(cc.identifiant::character varying(100), ' (', l.nom, ')')
							else
								cc.identifiant::character varying(100)
						end), 
						'|' order by cc.identifiant
					) as identifiant_hors_web
				from centrales c
				left join centrale_clinique cc ON cc.centrale_id = c.id
				left join laboratoires l on l.id = cc.supplier_id and l.obsolete is false
				where cc.web is false
				group by clinique_id, centrale_id
			) liste_centrales_hors_web ON liste_centrales_hors_web.clinique_id = c.clinique_id AND liste_centrales_hors_web.centrale_id = c.centrale_id
			left join
			(
				select cc.clinique_id AS clinique_id, cc.centrale_id, string_agg(cc.identifiant::character varying(100), '|' order by cc.identifiant) as identifiant_web
				from centrales c
				left join centrale_clinique cc ON cc.centrale_id = c.id
				where cc.web is true
				group by clinique_id, centrale_id
			) liste_centrales_web ON liste_centrales_web.clinique_id = c.clinique_id AND liste_centrales_web.centrale_id = c.centrale_id
		) t
		group by clinique_id, veterinaires, clinique, adresse, code_postal, ville, date_entree, date_left, country, commentaire";

		$result = DB::select(DB::raw($query));

        return $result;
	}

	public function findCAByObjectifId($objectifId, $annee)
	{
		$querySelect = "select clinique_id, clinique ";
  		$querySelectValues = "select (to_char(extract(month from t), ''99'') || ''-'' || to_char(extract(year from t), ''9999'')) AS mois from generate_series((''" . ($annee-1) . "-01-01'')::date, (''" . $annee . "-" . ($annee == date('Y') ? date('m') : '12') . "-01'')::date, ''1 month'') as t";
  		$queryCrosstabAs = "
			) AS t (clinique_id text, clinique text";
		
		$interval = date_diff(date_create(($annee-1) . '-01-01'), date_create($annee . '-' . ($annee == date('Y') ? date('m') : '12') . '-01'));
		$nbMois = (($interval->format('%y') * 12) + $interval->format('%m'));
		for($i=0 ; $i<$nbMois+1 ; $i++)
		{
			$querySelect .= "
			, ca_complet_M" . $i;

			$queryCrosstabAs .= "
			, ca_complet_M" . $i . " numeric";
		}
		$queryCrosstabAs .= ")";

  		$queryFrom = "
	  		FROM crosstab(
				\$\$select distinct cliniques_mois.clinique_id, cliniques_mois.clinique, (cliniques_mois.mois || '-' || cliniques_mois.annee) as mois, coalesce(round(sum(ca_periode)::numeric,2), 0) AS ca_complet
					FROM 
					(
						select c.id AS clinique_id, c.veterinaires as clinique, liste_mois.mois AS mois, liste_mois.annee AS annee
						FROM cliniques c,
						objectifs o
						join categories cat on cat.id = o.categorie_id,
						(select to_char(extract(month from t), '99') as mois, to_char(extract(year from t), '9999') as annee from generate_series(('" . ($annee-1) . "-01-01')::date, ('" . $annee . "-" . ($annee == date('Y') ? date('m') : '12') . "-01')::date, '1 month') as t) liste_mois
						WHERE c.obsolete is false
						and o.id = " . $objectifId . "
						and EXTRACT(YEAR from c.date_entree) < (cat.annee + 1)
						and c.country_id = cat.country_id
					) cliniques_mois
					LEFT JOIN
					(
						SELECT distinct cliniques.id AS clinique_id, achats.id, EXTRACT(month from achats.date) as mois, EXTRACT(year from achats.date) as annee, achats.qte_payante_complet AS volume, 
						(CASE objectifs.type_valorisation_objectif_id
							WHEN 1 THEN achats.ca_complet
							WHEN 2 THEN cpt.prix_unitaire_hors_promo * achats.qte_payante_complet
							WHEN 3 THEN produit_valorisations.valo_euro * achats.qte_payante_complet
						END) as ca_periode
						FROM objectifs
						JOIN categories ON categories.id = objectifs.categorie_id
						JOIN categorie_produit_objectif ON categorie_produit_objectif.objectif_id = objectifs.id
						JOIN categorie_produit ON categorie_produit.id = categorie_produit_objectif.categorie_produit_id
						JOIN produits ON produits.id = categorie_produit.produit_id
						JOIN achats ON achats.produit_id = produits.id AND achats.obsolete IS FALSE
						JOIN centrale_clinique on centrale_clinique.id = achats.centrale_clinique_id
						JOIN cliniques on cliniques.id = centrale_clinique.clinique_id and cliniques.obsolete is false and cliniques.country_id = categories.country_id
						LEFT JOIN produit_valorisations ON produit_valorisations.produit_id = achats.produit_id AND ((achats.date between produit_valorisations.date_debut AND produit_valorisations.date_fin) 
							or (achats.date >= produit_valorisations.date_debut AND produit_valorisations.date_fin is null))
						LEFT JOIN centrale_produit ON centrale_produit.id = achats.centrale_produit_id
						LEFT JOIN centrale_produit_tarifs cpt ON cpt.centrale_produit_id = centrale_produit.id AND achats.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
						WHERE objectifs.id = " . $objectifId . "
						AND produits.invisible IS FALSE
						AND achats.date BETWEEN to_date('01/01/" . ($annee-1) . "', 'DD/MM/YYYY') AND last_day(to_date('01/" . ($annee == date('Y') ? date('m') : '12') . "/" . $annee . "', 'DD/MM/YYYY'))
						AND EXTRACT(YEAR from cliniques.date_entree) < (categories.annee + 1)
						and cliniques.premium = objectifs.premium
						and ((objectifs.except_SE1107 is false) or (objectifs.except_SE1107 is true and cliniques.is_SE1107 is false))
					) liste_achats ON liste_achats.mois = cliniques_mois.mois::double precision and liste_achats.annee = cliniques_mois.annee::double precision AND liste_achats.clinique_id = cliniques_mois.clinique_id
					group by cliniques_mois.clinique_id, cliniques_mois.clinique, cliniques_mois.mois, cliniques_mois.annee
					order by clinique\$\$
				, '" . $querySelectValues . "'";
	
		$query = $querySelect . $queryFrom . $queryCrosstabAs;
		
		$result = DB::select(DB::raw($query));

        return $result;
	}

	public function findCodesCentralesById($id)
	{
		$query = $this->clinique
					->select('centrale_clinique.id as id', 'centrale_clinique.identifiant as name', DB::raw("'true' as selected"))
					->join('centrale_clinique','centrale_clinique.clinique_id', '=', 'cliniques.id')
					->where('cliniques.id', '=', $id)
					->orderBy('name');

		return $query->get();
	}

	public function findAllByParams($clinicYears, $selectedClinics, $userCliniqueId)
	{
		$params = [];
		$clinicYearsQuery = "";
		if ($clinicYears != null && sizeof($clinicYears) > 0)
		{
			$clinicYearsQuery = "and EXTRACT(YEAR from c.date_entree) in (" . implode(', ', $clinicYears) . ")";
			$params[] = implode(', ', $clinicYears);
		}

		$selectedClinicsQuery = "";
		if ($selectedClinics != null && sizeof($selectedClinics) > 0)
		{
			$selectedClinicsQuery = "
			
					union 

					select distinct c.id, c.nom, c.veterinaires, c.code_postal, c.ville
					from cliniques c
					where c.id in (" . implode(', ', $selectedClinics) . ")";
			$params[] = implode(', ', $selectedClinics);
		} 

		$query = "select distinct id, nom, veterinaires, code_postal, ville
				from
				(
					select distinct c.id, c.nom, c.veterinaires, c.code_postal, c.ville
					from cliniques c
					join centrale_clinique cc on cc.clinique_id = c.id
					where c.obsolete is false 
					" . ($userCliniqueId != null ? " and c.id = " . $userCliniqueId : "") . "
					" . $clinicYearsQuery . "
					" . $selectedClinicsQuery . "
				) liste";
		
		$result = DB::select(DB::raw($query));

		return $result;
	}

	public function findCountByParams($clinicYears, $userCliniqueId)
	{
		$params = [];
		$clinicYearsQuery = "";
		if ($clinicYears != null && sizeof($clinicYears) > 0)
		{
			$clinicYearsQuery = "and EXTRACT(YEAR from c.date_entree) in (" . implode(', ', $clinicYears) . ")";
			$params[] = implode(', ', $clinicYears);
		}
		
		$query = "select count(distinct c.id)
				from cliniques c
				join centrale_clinique cc on cc.clinique_id = c.id
				where c.obsolete is false 
				" . ($userCliniqueId != null ? " and c.id = " . $userCliniqueId : "") . "
				" . $clinicYearsQuery;
		
		$result = DB::select(DB::raw($query));

		return $result;
	}

	public function findCAByIdAndTargetId($clinicId, $clinicCodes, $targetId, $year, $estim, $startMonth, $startYear, $endMonth, $endYear)
	{
		$query = "
					select coalesce(sum(ca_unite)::numeric, 0) as ca_unite, coalesce(sum(ca_euro)::numeric, 0) as ca_euro, coalesce(sum(ca_euro * pourcentage_remise / 100)::numeric, 0) AS remise_euro
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
						END) AS ca_unite,
						(CASE type_valorisation_objectif_id 
							WHEN 1 THEN ca_complet 
							WHEN 2 THEN (prix_unitaire_hors_promo * volume)
							WHEN 3 THEN (valo_euro * volume)
							ELSE NULL 
						END) AS ca_euro
						from (
							select distinct o.id AS obj_id, a.id, a.date AS date, a.ca_complet, a.qte_payante_complet AS volume, pv.valo_euro, cpt.prix_unitaire_hors_promo, p.valo_volume, o.type_valorisation_objectif_id, o.valorisation_laboratoire, cpo.pourcentage_remise
							from objectifs o
							join categories cat on cat.id = o.categorie_id 
							join categorie_produit_objectif cpo on cpo.objectif_id = o.id 
							join categorie_produit cpr on cpr.id = cpo.categorie_produit_id 
							join produits p on p.id = cpr.produit_id
							left outer join achats a on a.produit_id = p.id and a.obsolete IS FALSE ";
		if ($estim)
		{
			$query .= "and (a.date between '" . Carbon::create($startYear, $startMonth, 1, 0, 0, 0) . "' and '" . Carbon::create($endYear, $endMonth, 1, 0, 0, 0)->endOfMonth() . "') and (extract(month from a.date) between extract(month from o.date_debut) and extract(month from o.date_fin))";
		} else 
		{
			$query .= "and a.obsolete IS FALSE AND EXTRACT(YEAR from a.date) = " . $year . " and (a.date between to_date('01/' || EXTRACT(MONTH from o.date_debut) || '/' || cat.annee, 'DD/MM/YYYY') and last_day(to_date('01/' || CASE cat.annee 
								WHEN EXTRACT(YEAR from current_date) THEN 
									CASE 
										WHEN EXTRACT(MONTH from o.date_fin) < " . $endMonth . " THEN EXTRACT(MONTH from o.date_fin)
										ELSE " . $endMonth . "
									END
								ELSE EXTRACT(MONTH from o.date_fin)
							END || '/' || cat.annee, 'DD/MM/YYYY')))";
		}

		$query .= "
							join centrale_clinique cc on cc.id = a.centrale_clinique_id 
							join cliniques c on c.id = cc.clinique_id and c.country_id = cat.country_id
							left join produit_valorisations pv on pv.produit_id = p.id and ((a.date between pv.date_debut and pv.date_fin) or (a.date >= pv.date_debut and pv.date_fin is null))
							left join centrale_produit cp on cp.id = a.centrale_produit_id
							left join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and a.date = cpt.date_creation and cpt.qte_tarif::numeric = 1
							where o.obsolete is false 
							and o.id = " . $targetId . "
							and c.id = " . $clinicId . "
							" . (($clinicCodes == null || in_array('0', $clinicCodes)) ? "" : ("and cc.id in (".implode(",", $clinicCodes).")")) . "
							AND p.invisible IS FALSE
						) achats_periode
					) t";
	
		$result = DB::select(DB::raw($query));

        return $result;
	}

}