<?php

namespace App\Repositories;

use DB;

class ParametrageRepository implements ParametrageRepositoryInterface
{
    /**
     * Returns value of purchases last update date (at format 'DD-MM-YYYY').
     */
	public function findPurchagesLastUpdateDate()
	{
		$result = DB::table('parametrage')
					->select(DB::raw("substring(valeur, 1, 10) AS valeur"))
					->where('nom', 'ACHATS_DATE_MAJ')
					->first();
		
		return $result->valeur;
	}

	/**
	* Returns value of commitments closing date.
	*/
	public function findCommitmentsClosingDate()
	{
		$result = DB::table('parametrage')
					->select('valeur')
					->where('nom', 'ENGAGEMENTS_DATE_SAISIE_MAX')
					->first();
		
		return $result->valeur;
	}
	
}