<?php 

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Model\Centrale;
use App\Model\Centrale_clinique;
use App\Model\Clinique;
use App\Model\Engagement;
use App\Repositories\CentraleRepository;
use App\Repositories\CliniqueRepository;
use App\Repositories\ObjectifRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;
use Validator;

class CliniqueAjaxController extends Controller 
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index(CliniqueRepository $cliniqueRepository)
  {
    $cliniques = $cliniqueRepository->findAll(null, Session::get('user_clinique_id'));
    
    return response()->json($cliniques);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request, CliniqueRepository $cliniqueRepository)
  {
    $validator = Validator::make($request->all(), [
        'name' => 'max:255',
        'veterinaries' => 'required|unique:cliniques,veterinaires|max:255',
        'city' => 'max:255',
        'year' => 'required|date_format:Y-m-d'
    ]);

    $input = $request->all();

    if ($validator->passes()) {
      // Create clinic
      $data = [
        'nom' => $request->name,
        'veterinaires' => $request->veterinaries,
        'ville' => $request->city,
        'date_entree' => $request->year
      ];
      $clinicId = Clinique::create($data)->id;

      // Création des engagements pour la clinique
      /*$insertEngagements = [];
      $objectifs = $objectifRepository->findAll($request->annee);
      foreach($objectifs as $objectif) {
          // Calcul du CA total N-1 pour la clinique
          $result = $objectifRepository->findCATotalPrecCliniqueById($objectif->id, $clinicId);

          $insertEngagements[] = [ 'objectif_id' => $objectif->id, 'clinique_id' => $clinicId, 'valeur' => $result->valeur_ca, 'valeur_auto' => true]; 
      }
      Engagement::insert($insertEngagements);*/

      $clinic = $cliniqueRepository->findById($clinicId);

      return response()->json(['success' => 1, 'clinic' => $clinic]);
    }
    
    return response()->json(['errors' => $validator->errors()]);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id, CliniqueRepository $clinicRepository)
  {
    $clinic = $clinicRepository->findDetailById($id);
    
    return response()->json(['clinic' => $clinic]);
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function edit($id)
  {
    
  }

  private function updateCentralsCodes($id, $centralsCodes)
  {
    if ($centralsCodes != null)
    {
      foreach ($centralsCodes as $centralCode) {
        $centId = $centralCode["centId"];
        $identifier = $centralCode["identifier"];
        $isWeb = (int)filter_var($centralCode["web"], FILTER_VALIDATE_BOOLEAN);

        // Search if identifier already exists in database : create if not exist
        $centClinic = Centrale_clinique::where('centrale_id', $centId)->where('identifiant', $identifier)->first();
        if ($centClinic == null)
        {
          $centClinicId = Centrale_clinique::create(['clinique_id' => $id, 'centrale_id' => $centId, 'identifiant' => $identifier, 'web' => $isWeb])->id;
        } else
        {
          $clinicName = Clinique::where('id', $centClinic["clinique_id"])->pluck('nom')->first();
          $centName = ucwords(strtolower(Centrale::where('id', $centId)->pluck('nom')->first()));
          return [ ["Le code centrale '" . $identifier . "' (" . $centName . ") existe déjà pour la clinique '" . $clinicName . "'" ] ];
        }
      }
    }
    return [];
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function update(Request $request, $id, CliniqueRepository $clinicRepository)
  {
    $validator = Validator::make($request->all(), [
        'clinic' => 'max:255',
        'veterinaries' => 'required|max:255|unique:cliniques,veterinaires,' . $id,
        'addresse' => 'max:255',
        'zipCode' => 'max:20',
        'city' => 'max:255',
        'year' => 'required|date_format:Y-m-d'
    ]);

    $input = $request->all();

    if ($validator->passes()) {
      // Update central codes (web and off web)
      $customErrors = new \stdClass();
      $cpt = 0;
      $errors = $this->updateCentralsCodes($id, $request->centralsCodes);
      if (sizeof($errors) > 0)
      {
        foreach ($errors as $key => $value) {
          $customErrors->{'identifiant' . $cpt} = $value;
          $cpt++;
        }
      }

      if ($cpt > 0)
      {
        return response()->json(['errors' => $customErrors]);
      }

      // Update clinic
      $data = [
        'nom' => $request->clinic,
        'veterinaires' => $request->veterinaries,
        'adresse' => $request->addresse,
        'code_postal' => $request->zipCode,
        'ville' => $request->city,
        'date_entree' => $request->year,
        'commentaire' => $request->comment
      ];
      Clinique::where('id', $id)->update($data);

      $clinic = $clinicRepository->findById($id);

      // Création des engagements éventuellement non existants pour la clinique
      /*$insertEngagements = [];
      $objectifs = $objectifRepository->findAll($request->year);
      foreach($objectifs as $objectif) {
        $engagement = Engagement::where('objectif_id', $objectif->id)->where('clinique_id', $id)->first();
        if ($engagement == null)
        {
          // Calcul du CA total N-1 pour la clinique
          $result = $objectifRepository->findCATotalPrecCliniqueById($objectif->id, $id);
          Engagement::insert([ 'objectif_id' => $objectif->id, 'clinique_id' => $id, 'valeur' => $result->valeur_ca, 'valeur_auto' => true]);
        }
      }*/

      return response()->json(['success' => 1, 'clinic' => $clinic]);
    }
    
    return response()->json(['errors' => $validator->errors()]);
  }

  /**
   * Passe les cliniques à obsolète.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy($id)
  {
    $ids = explode(",", $id);
    $deleteCliniques = Clinique::whereIn('id', $ids)->update(['obsolete' => 1]);

    Engagement::whereIn('clinique_id', $ids)->delete();
    
    return $deleteCliniques;
  }

  /**
   * Download balance of year-end discounts (Excel format).
   */
  public function downloadBilanRFA()
  {
    $filename = (date('Y')-1) . "_Synthèse_adhérent_Bourgelat_" . Session::get('user_clinique_id') . "_(" . str_replace('/', '-', Clinique::find(Session::get('user_clinique_id'))->veterinaires) . ").xlsx";
    
    return response()->download(storage_path("bilansFinAnnee/adherents/" . (date('Y')-1) . "/{$filename}"));
  }

  /**
   * Download the list of clinics.
   */
  public function downloadClinicsCSV(CliniqueRepository $clinicRepository, CentraleRepository $centralPurchasingRepository)
  {
    $clinics = $clinicRepository->findAllForExportCSV(null, Session::get('user_clinique_id'));
    $centralPurchasing = $centralPurchasingRepository->findAll();
  
    return view('cliniques/downloadClinicsCSV', compact('clinics', 'centralPurchasing'));
  }
}

?>