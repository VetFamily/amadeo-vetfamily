<?php

namespace App\Repositories;

interface AchatRepositoryInterface
{
	/**
	* Recherche la liste de toutes les statistiques.
	*/
	public function findAll($year);

    /*
	* Search purchases based on selected settings, displaying by products.
	*/
	public function findAllByProducts($startMonth, $startYear, $endMonth, $endYear, $byAnnee, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);

    /*
	* Search purchases based on selected settings, displaying by laboratories.
	*/
	public function findAllByLaboratories($startMonth, $startYear, $endMonth, $endYear, $byAnnee, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);

    /*
	* Search purchases based on selected settings, displaying by clinics.
	*/
	public function findAllByClinics($startMonth, $startYear, $endMonth, $endYear, $byAnnee, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);

    /*
	* Search purchases based on selected settings, displaying by categories.
	*/
	public function findAllByCategories($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);
}