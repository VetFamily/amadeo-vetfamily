<?php 

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Model\Engagement;
use App\Model\Objectif;
use App\Repositories\EngagementRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;
use Validator;

class EngagementAjaxController extends Controller 
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index(Request $request, EngagementRepository $engagementRepository)
  {
    $engagements = $engagementRepository->findAll(Session::get('user_clinique_id'));
    
    return response()->json($engagements);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    
  }

  private function updateEngagementsPaliersPrecedents($objectifId, $valeur)
  {
    $objectif = Objectif::where('id', $objectifId)->first();

    if ($objectif->objectif_precedent_id != null)
    {
      // Mise à jour de l'engagement associé à l'objectif précédent
      Engagement::where('clinique_id', Session::get('user_clinique_id'))->where('objectif_id', $objectif->objectif_precedent_id)->update(['valeur' => $valeur]);

      $this->updateEngagementsPaliersPrecedents($objectif->objectif_precedent_id, $valeur);
    }

    return;
  }

  private function updateEngagementsPaliersSuivants($objectifId, $valeur)
  {
    $objectif = Objectif::where('objectif_precedent_id', $objectifId)->first();

    if ($objectif != null)
    {
      // Mise à jour de l'engagement associé à l'objectif suivant
      Engagement::where('clinique_id', Session::get('user_clinique_id'))->where('objectif_id', $objectif->id)->update(['valeur' => $valeur]);

      $this->updateEngagementsPaliersSuivants($objectif->id, $valeur);
    }

    return;
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request)
  {
    foreach ($request->engagements as $engagement) {
      Engagement::where('id', $engagement["id"])->update(['valeur' => $engagement["valeur"]]);

      // S'il s'agit d'un objectif palier
      if ($engagement["type_objectif"] == 2)
      {
        // Mise à jour des engagements pour chaque palier précédent
        $this->updateEngagementsPaliersPrecedents($engagement["objectif"], $engagement["valeur"]);
        $this->updateEngagementsPaliersSuivants($engagement["objectif"], $engagement["valeur"]);
      }
    }
    
    return response()->json(['success' => 1]);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id)
  {
    
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

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return Response
   */
  public function update(Request $request, $id)
  {  
    
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy($id)
  {
    
  }
  
}

?>