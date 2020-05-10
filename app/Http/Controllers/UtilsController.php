<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\CategorieRepository;
use Illuminate\Support\Facades\Log;
use Session;

class UtilsController extends Controller
{
    public function searchCategorieByAnneeAndLaboratoire(CategorieRepository $categorieRepository)
	{
		$categories_tab = $categorieRepository->findByAnneeAndLaboratoire($_POST["annee"], $_POST["laboratoire"]);

		echo json_encode($categories_tab);
	}
	
}
