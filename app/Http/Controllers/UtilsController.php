<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\CategorieRepository;
use App\Repositories\LaboratoireRepository;
use Illuminate\Support\Facades\Log;
use Session;

class UtilsController extends Controller
{
    public function searchCategorieByCountryAndYearAndSupplier(CategorieRepository $catRepository)
	{
		$categories_tab = $catRepository->findByCountryAndYearAndSupplier($_POST["country"], $_POST["year"], $_POST["supplier"]);

		echo json_encode($categories_tab);
	}
	
    public function searchSuppliersByCountryAndYear(LaboratoireRepository $labRepository)
	{
		$suppliers_tab = $labRepository->findByCategoryForCountryAndYear($_POST["country"], $_POST["year"]);

		echo json_encode($suppliers_tab);
	}
	
}
