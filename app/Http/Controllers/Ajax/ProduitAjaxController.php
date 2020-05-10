<?php 

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Model\Espece_produit;
use App\Model\Produit;
use App\Model\Produit_type;
use App\Model\Produit_valorisations;
use App\Repositories\ProduitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;

class ProduitAjaxController extends Controller 
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index(ProduitRepository $produitRepository)
  {
    $produits = $produitRepository->findAll(Session::get('user_laboratoire_id'));
    
    return response()->json($produits);
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
  public function store(Request $request)
  {
    foreach ($request->produits as $produit) {
      $this->updateValorisationsProduit($produit["id"], $produit["valo_euro"], $produit["valo_volume"]);
    }
    
    return response()->json(['success' => 1]);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show(Request $request, ProduitRepository $productRepository, $id)
  {
    $centrals = $productRepository->findDetailById($id);

    return response()->json($centrals);
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

  private function updateTypesProduit($id, $selectTypes)
  {
    $oldTypes = Produit_type::where('produit_id', $id)->pluck('type_id')->toArray();
    
    // Suppression de tous les types de $oldTypes qui ne sont pas dans $selectTypes
    $typesToDelete = [];
    foreach ($oldTypes as $type) {
      if ($selectTypes == null || !in_array($type, $selectTypes))
      {
        $typesToDelete[] = $type;
      }
    }
    if (sizeof($typesToDelete) > 0)
    {
      Produit_type::where('produit_id', $id)->whereIn('type_id', $typesToDelete)->delete();
    }

    // Ajout de tous les types de $selectTypes qui ne sont pas dans $oldTypes
    $typesToInsert = [];
    if ($selectTypes != null)
    {
      foreach ($selectTypes as $type) {
        if (!in_array($type, $oldTypes))
        {
          $typesToInsert[] = ['produit_id' => $id, 'type_id' => $type];
        }
      }
      if (sizeof($typesToInsert) > 0)
      {
        Produit_type::insert($typesToInsert);
      }
    }
  }

  private function updateEspecesProduit($id, $selectEspeces)
  {
    $oldEspeces = Espece_produit::where('produit_id', $id)->pluck('espece_id')->toArray();
    
    // Suppression de toutes les espèces de $oldEspeces qui ne sont pas dans $selectEspeces
    $especesToDelete = [];
    foreach ($oldEspeces as $espece) {
      if ($selectEspeces == null || !in_array($espece, $selectEspeces))
      {
        $especesToDelete[] = $espece;
      }
    }
    if (sizeof($especesToDelete) > 0)
    {
      Espece_produit::where('produit_id', $id)->whereIn('espece_id', $especesToDelete)->delete();
    }

    // Ajout de toutes les espèces de $selectEspeces qui ne sont pas dans $oldEspeces
    $especesToInsert = [];
    if ($selectEspeces != null)  
    {
      foreach ($selectEspeces as $espece) {
        if (!in_array($espece, $oldEspeces))
        {
          $especesToInsert[] = ['produit_id' => $id, 'espece_id' => $espece];
        }
      }
      if (sizeof($especesToInsert) > 0)
      {
        Espece_produit::insert($especesToInsert);
      }
    }
  }

  private function updateValorisationsProduit($id, $valo_euro, $valo_volume)
  {
    $saveVal = null;
    $valo_euro = str_replace(',', '.', $valo_euro);
    $valo_volume = str_replace(',', '.', $valo_volume);
    $dateDebut = date("Y-m-01");
    $dateFin = date('Y-m-t');

    // Mise à jour de la valorisation en volume
    Produit::where('id', $id)->update(['valo_volume' => $valo_volume]);
    
    // Recherche de la valorisation en cours pour le produit
    $valEnCours = Produit_valorisations::where('produit_id', $id)->whereNull('date_fin')->first();

    if (sizeof($valEnCours) > 0)
    {
      if (!(floatval($valEnCours->valo_euro) == floatval($valo_euro)))
      {
        // Vérification du mois
        $mois = date("m", strtotime($valEnCours->date_debut));
        if ($mois == date("m"))
        {
          // Mise à jour de la valorisation en cours pour le produit
          $saveVal = Produit_valorisations::where('id', $valEnCours->id)->update(['valo_euro' => $valo_euro]);
        } else
        {
          // Clôture de la valorisation en cours pour le produit
          Produit_valorisations::where('id', $valEnCours->id)->update(['date_fin' => $dateFin]);

          // Ajout d'une nouvelle valorisation en cours pour le produit
          $saveVal = Produit_valorisations::insert(['produit_id' => $id, 'date_debut' => $dateDebut, 'valo_euro' => $valo_euro]);
        }
      }
    } else
    {
      // Création d'une valorisation pour le produit
      $saveVal = Produit_valorisations::insert(['produit_id' => $id, 'date_debut' => $dateDebut, 'valo_euro' => $valo_euro]);
    }

    return $saveVal;
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
    // Mise à jour du produit
    $data = [
      'denomination' => $request->denomination,
      'conditionnement' => $request->conditionnement
    ];
    $saveProduit = Produit::where('id', $id)->update($data);

    // Mise à jour des types
    $this->updateTypesProduit($id, $request->types);

    // Mise à jour des espèces
    $this->updateEspecesProduit($id, $request->especes);

    // Mise à jour des valorisations
    $this->updateValorisationsProduit($id, $request->valo_euro, $request->valo_volume);

    return response()->json($saveProduit);
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