<?php

namespace App\Repositories;

interface EtatsObjectifRepositoryInterface
{

    /**
     * Find all etats_objectifs 
     *
     * @param double $ecart
     * @return array
     */
    public function findAll();

    /**
     * Find the etats_objectif object corresponding to the ecart
     *
     * @param double $ecart
     * @return array
     */
    public function findEtatNonAtteintByEcart($ecart);

    /**
     * Returns an array of the sum of states (etats) for all RELEVANT objectifs for the given year (annee)
     * @param int $year Year of categories
     * @return array
     */
    public function findByYear(int $year);

    /**
     * Returns an array of RELEVANT objectifs for the given year (annee) and state (etat)
     * @param int $year Year of categories
     * @param string $stateId The name of state of the objectives
     * @return array
     */
    public function findByYearAndState(int $year, string $stateId);

    /**
     * Returns an array of a given objectif's ca_periode and ca_periode_precedant
     * @param int $id Id of objectif
     * @return array
     */
    public function findMonthlySesonalityByObjectifId($id);

    /**
     * Returns an array of a evolution and participation rates for the given objectif
     * @param int $id Id of objectif
     * @return array
     */
    public function findEvolutionAndParticipationRatesProducts($id);

    /**
     * Returns an array of a evolution and participation rates for the given objectif
     * @param int $id Id of objectif
     * @return array
     */
    public function findEvolutionAndParticipationRatesClinics($id);
}
