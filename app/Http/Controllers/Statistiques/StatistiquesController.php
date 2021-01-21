<?php

namespace App\Http\Controllers\Statistiques;

use App\Http\Controllers\Controller;
use App\Repositories\AchatRepository;
use App\Repositories\CliniqueRepository;
use App\Repositories\FilialeRepository;
use App\Repositories\LaboratoireRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;

class StatistiquesController extends Controller
{
    /*
    * Search purchases based on selected settings
    */
	public function getListOfPurchasesByParams(AchatRepository $purchaseRepository)
	{
		// Clinics
		$clinics = null;
		if (isset($_POST["clinics"]))
		{
			$clinics = $_POST["clinics"];
		}

		// Products
		$products = null;
		if (isset($_POST["products"]))
		{
			$products = $_POST["products"];
		}

		$displayType = null;
		if (isset($_POST["displayType"]))
		{
			$displayType = $_POST["displayType"];
		}

		// Valorization
		$valorization = null;
		if (isset($_POST["valorization"]))
		{
			$valorization = $_POST["valorization"];
		}

		// Central purchasing
		$centralPurchasing = null;
		if (isset($_POST["centralPurchasing"]))
		{
			$centralPurchasing = $_POST["centralPurchasing"];
		}
		
		// Period
		$startMonth = $_POST["startMonth"];
		$startYear = $_POST["startYear"];
		$endMonth = $_POST["endMonth"];
		$endYear = $_POST["endYear"];
		$byYear = (bool) $_POST["byYear"];
		$nbMonthDiff = $_POST["nbMonthDiff"];

		switch ($displayType[0]) {
			case 'product':
				$purchases = $purchaseRepository->findAllByProducts($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);
				break;

			case 'laboratory':
				$purchases = $purchaseRepository->findAllByLaboratories($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);
				break;

			case 'clinic':
				$purchases = $purchaseRepository->findAllByClinics($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);
				break;

			case 'category':
				$purchases = $purchaseRepository->findAllByCategories($startMonth, $startYear, $endMonth, $endYear, $byYear, $clinics, $products, $valorization, $centralPurchasing, $nbMonthDiff);
				break;
			
			default:
				$purchases = null;
				break;
		}

		// Setting purchases criteria in session
		Session::put('purchasesCriteria', [
			"startMonth" => $startMonth,
			"startYear" => $startYear,
			"endMonth" => $endMonth,
			"endYear" => $endYear,
			"byYear" => $byYear,
			"nbMonthDiff" => $nbMonthDiff,
			"clinics" => $clinics,
			"products" => $products,
			"displayType" => $displayType,
			"valorization" => $valorization,
			"centralPurchasing" => $centralPurchasing,
		]);

		Session::put('purchases', $purchases);

		echo json_encode($purchases);
	}

	/*
    * Recherche les statistiques complètes du groupement pour téléchargement d'un CSV.
    */
	public function downloadPurchasesCSV($startMonth, $startYear, $endMonth, $endYear, $countryId, $sourceId, $supplierId, AchatRepository $purchaseRepository)
	{
		$purchases = $purchaseRepository->findAll($startMonth, $startYear, $endMonth, $endYear, $countryId, $sourceId, $supplierId);

		return view('statistiques/downloadAchatsCSV', compact('purchases', 'startMonth', 'startYear', 'endMonth', 'endYear', 'countryId', 'sourceId', 'supplierId'));
	}

	/*
    * Recherche les statistiques filtrées du groupement pour téléchargement d'un CSV.
    */
	public function downloadPurchasesByParamsCSV(AchatRepository $purchaseRepository)
	{
	    // Getting purchases criteria
	    $purchasesClinicsCriteria = Session::get('purchases-clinicsCriteria');
	    $purchasesProductsCriteria = Session::get('purchases-productsCriteria');
	    $purchasesCriteria = Session::get('purchasesCriteria');

	    // Getting purchases
		switch (Session::get('purchasesCriteria')["displayType"][0]) {
			case 'product':
				$purchases = $purchaseRepository->findAllByProducts($purchasesCriteria["startMonth"], $purchasesCriteria["startYear"], $purchasesCriteria["endMonth"], $purchasesCriteria["endYear"], true/*$purchasesCriteria["byYear"]*/, $purchasesCriteria["clinics"], $purchasesCriteria["products"], $purchasesCriteria["valorization"], $purchasesCriteria["centralPurchasing"], $purchasesCriteria["nbMonthDiff"]);
				break;

			case 'laboratory':
				$purchases = $purchaseRepository->findAllByLaboratories($purchasesCriteria["startMonth"], $purchasesCriteria["startYear"], $purchasesCriteria["endMonth"], $purchasesCriteria["endYear"], true/*$purchasesCriteria["byYear"]*/, $purchasesCriteria["clinics"], $purchasesCriteria["products"], $purchasesCriteria["valorization"], $purchasesCriteria["centralPurchasing"], $purchasesCriteria["nbMonthDiff"]);
				break;

			case 'clinic':
				$purchases = $purchaseRepository->findAllByClinics($purchasesCriteria["startMonth"], $purchasesCriteria["startYear"], $purchasesCriteria["endMonth"], $purchasesCriteria["endYear"], true/*$purchasesCriteria["byYear"]*/, $purchasesCriteria["clinics"], $purchasesCriteria["products"], $purchasesCriteria["valorization"], $purchasesCriteria["centralPurchasing"], $purchasesCriteria["nbMonthDiff"]);
				break;

			case 'category':
				$purchases = $purchaseRepository->findAllByCategories($purchasesCriteria["startMonth"], $purchasesCriteria["startYear"], $purchasesCriteria["endMonth"], $purchasesCriteria["endYear"], true/*$purchasesCriteria["byYear"]*/, $purchasesCriteria["clinics"], $purchasesCriteria["products"], $purchasesCriteria["valorization"], $purchasesCriteria["centralPurchasing"], $purchasesCriteria["nbMonthDiff"]);
				break;
			
			default:
				$purchases = null;
				break;
		}

		return view('statistiques/downloadAchatsByParamsCSV', compact('purchases', 'purchasesClinicsCriteria', 'purchasesProductsCriteria', 'purchasesCriteria'));
	}
}
