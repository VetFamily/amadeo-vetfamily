<?php

namespace App\Http\Controllers\Commun;

use App\Http\Controllers\Controller;
use App\Repositories\AchatRepository;
use App\Repositories\CentraleRepository;
use App\Repositories\CliniqueRepository;
use App\Repositories\CountryRepository;
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
    private $achatRepository;
    private $parametrageRepository;
    private $roleRepository;
    private $especeRepository;
    private $cliniqueRepository;
    private $typeRepository;
    private $centraleRepository;
    private $typeObjectifRepository;
    private $typeValorisationObjectifRepository;
    private $laboratoireRepository;
    private $countryRepository;
  
    public function __construct(AchatRepository $achatRepository, ParametrageRepository $parametrageRepository, RoleRepository $roleRepository, EspeceRepository $especeRepository, CliniqueRepository $cliniqueRepository, TypeRepository $typeRepository, CentraleRepository $centraleRepository, TypeObjectifRepository $typeObjectifRepository, TypeValorisationObjectifRepository $typeValorisationObjectifRepository, LaboratoireRepository $laboratoireRepository, CountryRepository $countryRepository)
    {
        $this->achatRepository = $achatRepository;
        $this->parametrageRepository = $parametrageRepository;
        $this->roleRepository = $roleRepository;
        $this->especeRepository = $especeRepository;
        $this->cliniqueRepository = $cliniqueRepository;
        $this->typeRepository = $typeRepository;
        $this->centraleRepository = $centraleRepository;
        $this->typeObjectifRepository = $typeObjectifRepository;
        $this->typeValorisationObjectifRepository = $typeValorisationObjectifRepository;
        $this->laboratoireRepository = $laboratoireRepository;
        $this->countryRepository = $countryRepository;
    }
  
    public function getUserInformations()
    {
        if ((sizeof(Auth::user()->roles) >0) && ("Vétérinaire" == Auth::user()->roles[0]['nom']) && Session::get('user_clinique_id') == null)
        {
            // Recherche de la clinique de l'utilisateur
            Session::put('user_clinique_id', $this->roleRepository->findCliniqueIdByUserId(Auth::user()->id));
        } else if ((sizeof(Auth::user()->roles) >0) && ("Laboratoire" == Auth::user()->roles[0]['nom']) && Session::get('user_laboratoire_id') == null)
        {
            // Recherche du laboratoire de l'utilisateur
            Session::put('user_laboratoire_id', $this->roleRepository->findLaboratoireIdByUserId(Auth::user()->id));
        } else if ((sizeof(Auth::user()->roles) >0) && ("Administrateur" == Auth::user()->roles[0]['nom']) && Session::get('user_is_super_admin') == null)
        {
            // Recherche du caractère super admin de l'utilisateur
            Session::put('user_is_super_admin', $this->roleRepository->isUserSuperAdmin(Auth::user()->id));
        }
    }

	public function showTableauDeBord()
    {
    	$this->getUserInformations();
        Session::put('last_date', $this->achatRepository->findLastDateOfPurchasesByYear(null));
        
        return view('tableaudebord/tableaudebord');
    }

    /*
    * Affiche l'onglet "Détail des chiffres"
    */
    public function showOngletStatistiques()
    {
        $this->getUserInformations();
        Session::put('list_of_laboratories', $this->laboratoireRepository->findAllForSelect());
        Session::put('list_of_types', $this->typeRepository->findAll());
        Session::put('list_of_species', $this->especeRepository->findAll());
        Session::put('list_of_central_purchasing', $this->centraleRepository->findAll());
        Session::put('last_date', $this->achatRepository->findLastDateOfPurchasesByYear(null));
        
        return view('statistiques/statistiques');
    }

    public function showCliniques()
    {
    	$this->getUserInformations();
        
        return view('cliniques/cliniques');
    }

    public function showProduits()
    {
    	$this->getUserInformations();
        Session::put('list_of_types', $this->typeRepository->findAll());
        Session::put('list_of_species', $this->especeRepository->findAll());
        
        return view('produits/produits');
    }

    public function showCategories()
    {
        $this->getUserInformations();
        Session::put('laboratoires_liste', $this->laboratoireRepository->findAll());
        Session::put('list_of_species', $this->especeRepository->findAll());
        Session::put('list_of_countries', $this->countryRepository->findAll());

    	return view('categories/categories');
    }

    public function showObjectifs()
    {
        $this->getUserInformations();
        Session::put('laboratoires_liste', $this->laboratoireRepository->findAll());
        Session::put('list_of_species', $this->especeRepository->findAll());
        Session::put('types_objectif_liste', $this->typeObjectifRepository->findAll());
        Session::put('types_valorisations_liste', $this->typeValorisationObjectifRepository->findAll());
        Session::put('list_of_countries', $this->countryRepository->findAll());
        
        return view('objectifs/objectifs');
    }

    public function showEngagements()
    {
        $this->getUserInformations();
        Session::put('list_of_species', $this->especeRepository->findAll());
        Session::put('last_date', $this->achatRepository->findLastDateOfPurchasesByYear(null));
        
        return view('engagements/engagements');
    }

    public function showAdministration()
    {
        $this->getUserInformations();
        Session::put('cliniques_liste', $this->cliniqueRepository->findAllForSelect(null));
        
        return view('administration/administration');
    }
}
