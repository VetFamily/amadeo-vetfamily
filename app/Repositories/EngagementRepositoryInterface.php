<?php

namespace App\Repositories;

interface EngagementRepositoryInterface
{
    /**
	* Recherche la liste de tous les engagements d'une clinique (si une clinique est passée en paramètre) ou de toutes
	* les cliniques.
	*/
	public function findAll($cliniqueId);

	/**
	 * Returns commitment informations.
	 */
	public function findById($id);

    /**
     * Returns the list of the clinic's commitments by target id.
     */
    public function findDetailClinicsByTargetId($targetId);

	public function findAllForExportBilan($cliniqueId, $annee);

	/**
     * Returns the list of the clinic's discounts by laboratory.
     */
    public function findAllForDiscountsEstimation($clinicId, $year);
}