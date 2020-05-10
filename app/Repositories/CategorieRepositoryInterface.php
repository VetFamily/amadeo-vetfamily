<?php

namespace App\Repositories;

interface CategorieRepositoryInterface
{
	/**
	* Recherche la liste de toutes les catégories d'un laboratoire (si un laboratoire est passé en paramètre) ou de tous
	* les laboratoires.
	*/
	public function findAll($laboratoireId);

	public function findById($id);

	public function findListCommentsByCategorieId($id);

	public function findByParams($moisDeb, $anneeDeb, $moisFin, $anneeFin, $cliniques, $anneesCliniques, $laboratoires, $types, $especes);

	public function findByAnneeAndLaboratoire($annee, $laboratoireId);
}