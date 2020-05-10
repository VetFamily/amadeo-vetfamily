<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use App\Repositories\CliniqueRepository;
use App\Repositories\FamilleTherapeutiqueRepository;
use App\Repositories\ProduitRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;

class ParametrageController extends Controller
{
	/*
	 * Search the count of clinics based on selected settings
	 */
	public function getCountOfClinicsByParams(CliniqueRepository $clinicRepository) 
	{
		// Year of entry
		$clinicYears = null;
		if (isset($_POST["clinicYears"]))
		{
			$clinicYears = $_POST["clinicYears"];
		}

		$count = $clinicRepository->findCountByParams($clinicYears, Session::get('user_clinique_id'));

		echo json_encode($count);
	}

	/*
	* Search clinics based on selected settings
	*/
	public function getListOfClinicsByParams(CliniqueRepository $clinicRepository)
	{	
		// Year of entry
		$clinicYears = null;
		if (isset($_POST["clinicYears"]))
		{
			$clinicYears = $_POST["clinicYears"];
		}

		// Selected clinics
		$selectedClinics = null;
		if (isset($_POST["selectedClinics"]))
		{
			$selectedClinics = $_POST["selectedClinics"];
		}

		$clinics = $clinicRepository->findAllByParams($clinicYears, $selectedClinics, Session::get('user_clinique_id'));

		if (isset($_POST["currentScreen"]))
		{
			// Setting clinics criteria in session
			Session::put($_POST["currentScreen"] . '-clinicsCriteria', [
				"clinicYears" => $clinicYears
			]);
		}

 		echo json_encode($clinics);
	}

	/*
	* Search therapeutic classes
	*/
	public function getListOfTherapeuticClassesByParams(FamilleTherapeutiqueRepository $therapeuticClassRepository)
	{
		// Laboratories
		$laboratories = null;
		if (isset($_POST["laboratories"]))
		{
			$laboratories = $_POST["laboratories"];
		}
		
		// Product types
		$productTypes = null;
		if (isset($_POST["productTypes"]))
		{
			$productTypes = $_POST["productTypes"];
		}
		
		// Product species
		$productSpecies = null;
		if (isset($_POST["productSpecies"]))
		{
			$productSpecies = $_POST["productSpecies"];
		}

		$classes = $therapeuticClassRepository->findAllByParams($laboratories, $productTypes, $productSpecies);

		if (isset($_POST["currentScreen"]))
		{
			// Setting products criteria in session
			Session::put($_POST["currentScreen"] . '-productsCriteria', [
				"laboratories" => $laboratories,
				"productTypes" => $productTypes,
				"productSpecies" => $productSpecies
			]);
		}

        echo json_encode($classes);
	}

	/*
	 * Search the count of products based on selected settings
	 */
	public function getCountOfProductsByParams(ProduitRepository $productRepository) 
	{
		// Laboratories
		$laboratories = null;
		if (isset($_POST["laboratories"]))
		{
			$laboratories = $_POST["laboratories"];
		}

		// Product types
		$productTypes = null;
		if (isset($_POST["productTypes"]))
		{
			$productTypes = $_POST["productTypes"];
		}

		// Product species
		$productSpecies = null;
		if (isset($_POST["productSpecies"]))
		{
			$productSpecies = $_POST["productSpecies"];
		}

		// Therapeutic classes
		$therapeuticClasses = null;
		if (isset($_POST["therapeuticClasses"]))
		{
			$therapeuticClasses = $_POST["therapeuticClasses"];
		}

		$count = $productRepository->findCountByParams($laboratories, $productTypes, $productSpecies, $therapeuticClasses);

		echo json_encode($count);
	}

	/*
	* Search products based on selected settings
	*/
	public function getListOfProductsByParams(ProduitRepository $productRepository)
	{	
		// Laboratories
		$laboratories = null;
		if (isset($_POST["laboratories"]))
		{
			$laboratories = $_POST["laboratories"];
		}
		
		// Product types
		$productTypes = null;
		if (isset($_POST["productTypes"]))
		{
			$productTypes = $_POST["productTypes"];
		}
		
		// Product species
		$productSpecies = null;
		if (isset($_POST["productSpecies"]))
		{
			$productSpecies = $_POST["productSpecies"];
		}

		// Therapeutic classes
		$therapeuticClasses = null;
		if (isset($_POST["therapeuticClasses"]))
		{
			$therapeuticClasses = $_POST["therapeuticClasses"];
		}

		// Selected products
		$selectedProducts = null;
		if (isset($_POST["selectedProducts"]))
		{
			$selectedProducts = $_POST["selectedProducts"];
		}

		$products = $productRepository->findAllByParams($laboratories, $productTypes, $productSpecies, $therapeuticClasses, $selectedProducts);

		if (isset($_POST["currentScreen"]))
		{
			// Setting products criteria in session
			Session::put($_POST["currentScreen"] . '-productsCriteria', [
				"laboratories" => $laboratories,
				"productTypes" => $productTypes,
				"productSpecies" => $productSpecies,
				"therapeuticClasses" => $therapeuticClasses
			]);
		}

 		echo json_encode($products);
	}
}