<?php

namespace App\Repositories;

use App\Model\FamilleTherapeutique;
use DB;

class FamilleTherapeutiqueRepository implements FamilleTherapeutiqueRepositoryInterface
{

    protected $famille;

	public function __construct(FamilleTherapeutique $famille)
	{
		$this->famille = $famille;
	}

	public function findAll()
	{
        return $this->famille->where('obsolete', '=', '0')->orderBy('nom')->get();
	}

	public function findAllByParams($laboratories, $productTypes, $productSpecies)
	{
		$params = [];
		$laboratoriesQuery = "";
		if ($laboratories != null && sizeof($laboratories) > 0)
		{
			$laboratoriesQuery = "join laboratoires l on l.id = p.laboratoire_id and p.laboratoire_id in (" . implode(', ', $laboratories) . ")";
			$params[] = implode(', ', $laboratories);
		}
		$productTypesQuery = "";
		if ($productTypes != null && sizeof($productTypes) > 0)
		{
			$productTypesQuery = "join produit_type pt on pt.produit_id = p.id and pt.type_id in (" . implode(', ', $productTypes) . ")";
			$params[] = implode(', ', $productTypes);
		}
		$productSpeciesQuery = "";
		if ($productSpecies != null && sizeof($productSpecies) > 0)
		{
			$productSpeciesQuery = "join espece_produit ep on ep.produit_id = p.id and ep.espece_id in (" . implode(', ', $productSpecies) . ")";
			$params[] = implode(', ', $productSpecies);
		}

		$query = "select distinct ft.id, ft.classe1_code, ft.classe1_nom, ft.classe2_code, ft.classe2_nom, ft.classe3_code, ft.classe3_nom
				from familles_therapeutiques ft
				join produits p on p.famille_therapeutique_id = ft.id
				" . $laboratoriesQuery . "
				" . $productTypesQuery . "
				" . $productSpeciesQuery . "
				where ft.obsolete is false";
				
		$result = DB::select(DB::raw($query));

		return $result;
	}
}