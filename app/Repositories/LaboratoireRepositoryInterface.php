<?php

namespace App\Repositories;

interface LaboratoireRepositoryInterface
{

    public function findAllForSelect();

    public function findAll();

    /**
	* Recherche la liste de tous les laboratoires avec les estimations de RFA pour téléchargement au format Excel.
	*/
	public function findEstimationsRFAForExcel($moisDeb, $anneeDeb, $moisFin, $anneeFin, $clinique, $codesCliniques, $anneeObj);

	public function findBilanRFAForExcel($annee);

	public function findCACliniqueById($laboratoireId, $cliniqueId, $moisDeb, $moisFin, $annee);
}