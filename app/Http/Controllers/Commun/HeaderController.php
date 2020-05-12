<?php

namespace App\Http\Controllers\Commun;

use App\Http\Controllers\Controller;
use App\Repositories\CentraleRepository;
use App\Repositories\CliniqueRepository;
use App\Repositories\EspeceRepository;
use App\Repositories\LaboratoireRepository;
use App\Repositories\ParametrageRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TypeRepository;
use App\Repositories\TypeObjectifRepository;
use App\Repositories\TypeValorisationObjectifRepository;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;

class HeaderController extends Controller
{
    public function getUserInformations($roleRepository)
    {
        if ((sizeof(Auth::user()->roles) >0) && ("Vétérinaire" == Auth::user()->roles[0]['nom']) && Session::get('user_clinique_id') == null)
        {
            // Recherche de la clinique de l'utilisateur
            Session::put('user_clinique_id', $roleRepository->findCliniqueIdByUserId(Auth::user()->id));
        } else if ((sizeof(Auth::user()->roles) >0) && ("Laboratoire" == Auth::user()->roles[0]['nom']) && Session::get('user_laboratoire_id') == null)
        {
            // Recherche du laboratoire de l'utilisateur
            Session::put('user_laboratoire_id', $roleRepository->findLaboratoireIdByUserId(Auth::user()->id));
        } else if ((sizeof(Auth::user()->roles) >0) && ("Administrateur" == Auth::user()->roles[0]['nom']) && Session::get('user_is_super_admin') == null)
        {
            // Recherche du caractère super admin de l'utilisateur
            Session::put('user_is_super_admin', $roleRepository->isUserSuperAdmin(Auth::user()->id));
        }
    }

	public function showTableauDeBord(ParametrageRepository $parametrageRepository, RoleRepository $roleRepository)
    {
    	$this->getUserInformations($roleRepository);
        Session::put('date_maj', $parametrageRepository->findPurchagesLastUpdateDate());
        
        return view('tableaudebord/tableaudebord');
    }

    /*
    * Affiche l'onglet "Détail des chiffres"
    */
    public function showOngletStatistiques(CliniqueRepository $cliniqueRepository, 
        LaboratoireRepository $laboratoireRepository, TypeRepository $typeRepository, EspeceRepository $especeRepository, ParametrageRepository $parametrageRepository, RoleRepository $roleRepository, CentraleRepository $centraleRepository)
    {
        $this->getUserInformations($roleRepository);
        Session::put('list_of_laboratories', $laboratoireRepository->findAllForSelect());
        Session::put('list_of_types', $typeRepository->findAll());
        Session::put('list_of_species', $especeRepository->findAll());
        Session::put('list_of_central_purchasing', $centraleRepository->findAll());
        Session::put('date_maj', $parametrageRepository->findPurchagesLastUpdateDate());
        
        return view('statistiques/statistiques');
    }

    public function showCliniques(RoleRepository $roleRepository)
    {
    	$this->getUserInformations($roleRepository);
        
        return view('cliniques/cliniques');
    }

    public function showProduits(TypeRepository $typeRepository, EspeceRepository $especeRepository, RoleRepository $roleRepository)
    {
    	$this->getUserInformations($roleRepository);
        Session::put('list_of_types', $typeRepository->findAll());
        Session::put('list_of_species', $especeRepository->findAll());
        
        return view('produits/produits');
    }

    public function showCategories(ParametrageRepository $parametrageRepository, EspeceRepository $especeRepository, LaboratoireRepository $laboratoireRepository, RoleRepository $roleRepository)
    {
        $this->getUserInformations($roleRepository);
        Session::put('laboratoires_liste', $laboratoireRepository->findAll());
        Session::put('list_of_species', $especeRepository->findAll());
        Session::put('date_maj', $parametrageRepository->findPurchagesLastUpdateDate());

    	return view('categories/categories');
    }

    public function showObjectifs(ParametrageRepository $parametrageRepository, EspeceRepository $especeRepository, LaboratoireRepository $laboratoireRepository, RoleRepository $roleRepository, TypeObjectifRepository $typeObjectifRepository, TypeValorisationObjectifRepository $typeValorisationObjectifRepository)
    {
        $this->getUserInformations($roleRepository);
        Session::put('laboratoires_liste', $laboratoireRepository->findAll());
        Session::put('list_of_species', $especeRepository->findAll());
        Session::put('types_objectif_liste', $typeObjectifRepository->findAll());
        Session::put('types_valorisations_liste', $typeValorisationObjectifRepository->findAll());
        Session::put('date_maj', $parametrageRepository->findPurchagesLastUpdateDate());
        
        return view('objectifs/objectifs');
    }

    public function showEngagements(ParametrageRepository $parametrageRepository, EspeceRepository $especeRepository, RoleRepository $roleRepository)
    {
        $this->getUserInformations($roleRepository);
        Session::put('list_of_species', $especeRepository->findAll());
        Session::put('date_maj', $parametrageRepository->findPurchagesLastUpdateDate());
        
        return view('engagements/engagements');
    }

    public function showAdministration(RoleRepository $roleRepository, CliniqueRepository $cliniqueRepository)
    {
        $this->getUserInformations($roleRepository);
        Session::put('cliniques_liste', $cliniqueRepository->findAllForSelect(null));
        
        return view('administration/administration');
    }
}
