<?php

namespace App\Http\Controllers\Statistiques;

use App\Http\Controllers\Controller;
use App\Repositories\CliniqueRepository;
use Illuminate\Support\Facades\Log;
use Session;

class StatistiquesObjectifsController extends Controller
{
	/*
    * Recherche le détail des CA par clinique et par mois pour un objectif pour téléchargement d'un CSV.
    */
	public function downloadObjectifParCliniquesCSV($objectifId, $annee, CliniqueRepository $cliniqueRepository)
	{
		$cliniques = $cliniqueRepository->findCAByObjectifId($objectifId, $annee);

		return view('objectifs/downloadObjectifParCliniquesCSV', compact('cliniques', 'objectifId', 'annee'));
	}
}
