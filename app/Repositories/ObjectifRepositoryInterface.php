<?php

namespace App\Repositories;

interface ObjectifRepositoryInterface
{

	/**
	 * Calculate the poids of the objectif
	 *
	 * @param  App\Model\Objectif $objectif
	 * @return double poids
	 */
	public function calculatePoids($objectif);

	/**
	 * Sets the etat_objectif_id column value of the given objectif
	 *
	 * @param Objectif $objectif
	 * @param array $ecartData
	 * @return Objectif
	 */
	public function setEtat($objectif, $ecartData);

	/**
	 * Updates the objectif using the values passed in the request
	 *
	 * @param App\Model\Objectif $objectif
	 * @param  \Illuminate\Http\Request  $request
	 * @return App\Model\Objectif $objectif
	 */
	public function updateObjectifFromRequest($objectif, $request);

	/**
	 * Update the CA, valeur atteinte, ecart and etat_objectif_id
	 *
	 * @param App\Model\Objectif $objectif
	 * @param \Illuminate\Http\Request  $request
	 * @param int mois_fin_CA
	 * @return void
	 */
	public function updateCAStateAndEcart($objectif, $mois_fin_CA);

	/**
	 * Recherche la liste de tous les objectifs d'un laboratoire (si un laboratoire est passé en paramètre) ou de tous
	 * les laboratoires.
	 */
	public function findByLaboratoireIdAndMoisFin($laboratoireId, $moisFin);

	public function findById($id);

	public function findCAById($id, $typeValorisation, $endMonth);

	public function findAll($annee);

	public function findEstimationsRFAForExcel($moisDeb, $anneeDeb, $moisFin, $anneeFin, $clinique, $laboratoire, $codesCliniques, $anneeObj);

	public function findObjectifsAtteints($annee, $moisFin);

	public function findObjectifsNonAtteints($annee, $moisFin);

	public function findObjectifPrecedent($objectifId, $annee, $moisFin);

	public function findCACliniqueById($objectifId, $cliniqueId, $moisFin);

	public function findCATotalPrecCliniqueById($objectifId, $cliniqueId);
}
